#!/usr/bin/env python3
"""
Classify differences between two feedback report snapshots.

    python3 scripts/feedback_snapshot_diff.py <baseline-dir> <candidate-dir>

Every difference is put in exactly one bucket:

  BENIGN-CASE-VARIANT  the two values are the same multiset of strings once
                       case-folded. GROUP_CONCAT(DISTINCT ...) under a _ci
                       collation keeps an arbitrary case-variant of duplicate
                       remarks, so this is pre-existing non-determinism, not a
                       data change.
  REAL                 anything else -- must be investigated.

Cases named *.page* slice an ordered result with OFFSET/LIMIT. The original
ORDER BY was not a total order, so which rows land on which page was undefined;
adding a deterministic tie-break necessarily moves rows across page boundaries.
Those cases are therefore reported separately and are not failures -- the
*.FULLSET cases carry the real "same data" assertion, and PAGING_INTEGRITY
checks that walking all pages now yields every row exactly once.

Exit status is non-zero if any REAL difference is found.
"""
import json
import re
import sys
from pathlib import Path

SEPARATORS = [' ~ ', ' | ', '|||', '\n']


def comment_multiset(s):
    """If s looks like a joined bundle of remarks, return it as a case-folded multiset."""
    for sep in SEPARATORS:
        if sep in s:
            parts = [p.strip().lower() for p in s.split(sep)]
            return sorted(p for p in parts if p)
    return None


def classify(a, b, path=''):
    """Yield (path, kind, detail) for each leaf difference between a and b."""
    if type(a) is not type(b):
        yield (path, 'REAL', f'type {type(a).__name__} -> {type(b).__name__}')
        return

    if isinstance(a, dict):
        for k in sorted(set(a) | set(b)):
            if k not in a or k not in b:
                yield (f'{path}.{k}', 'REAL', 'key added/removed')
            else:
                yield from classify(a[k], b[k], f'{path}.{k}')
        return

    if isinstance(a, list):
        if len(a) != len(b):
            yield (path, 'REAL', f'length {len(a)} -> {len(b)}')
            return
        for i, (x, y) in enumerate(zip(a, b)):
            yield from classify(x, y, f'{path}[{i}]')
        return

    if a == b:
        return

    if isinstance(a, str) and isinstance(b, str):
        ma, mb = comment_multiset(a), comment_multiset(b)
        if ma is not None and ma == mb:
            yield (path, 'BENIGN-CASE-VARIANT', 'same remarks, different case-variant kept')
            return
        if a.lower() == b.lower():
            yield (path, 'BENIGN-CASE-VARIANT', f'{a!r} -> {b!r}')
            return

    yield (path, 'REAL', f'{str(a)[:120]!r} -> {str(b)[:120]!r}')


def main():
    base, cand = Path(sys.argv[1]), Path(sys.argv[2])
    mb = json.loads((base / 'MANIFEST.json').read_text())
    mc = json.loads((cand / 'MANIFEST.json').read_text())

    only_base = sorted(set(mb) - set(mc))
    only_cand = sorted(set(mc) - set(mb))
    for k in only_base:
        print(f'  MISSING in candidate: {k}')
    for k in only_cand:
        print(f'  EXTRA in candidate:   {k}')

    changed = [k for k in mb if k in mc and mb[k]['sha1'] != mc[k]['sha1']]
    identical = len(mb) - len(changed) - len(only_base)

    totals = {'BENIGN-CASE-VARIANT': 0, 'REAL': 0}
    real_cases = []
    paging_cases = []

    for case in sorted(changed):
        f = re.sub(r'[^a-zA-Z0-9_.-]', '_', case) + '.json'
        a = json.loads((base / f).read_text())
        b = json.loads((cand / f).read_text())

        if '.page' in case or 'PAGING_INTEGRITY' in case or 'FULLSET.walk' in case:
            paging_cases.append(case)
            print(f'  PAGING  {case:<44} (row/page boundaries expected to move)')
            continue

        kinds = {}
        details = []
        for p, kind, detail in classify(a, b):
            kinds[kind] = kinds.get(kind, 0) + 1
            totals[kind] = totals.get(kind, 0) + 1
            if kind == 'REAL' and len(details) < 5:
                details.append(f'{p}: {detail}')
        verdict = 'REAL' if kinds.get('REAL') else 'BENIGN'
        if verdict == 'REAL':
            real_cases.append(case)
        print(f'  {verdict:<7} {case:<44} ' +
              ' '.join(f'{k}={v}' for k, v in sorted(kinds.items())))
        for d in details:
            print(f'            {d}')

    print()
    print(f'  cases={len(mb)}  byte-identical={identical}  differing={len(changed)}')
    print(f'  leaf diffs: benign-case-variant={totals["BENIGN-CASE-VARIANT"]}  REAL={totals["REAL"]}')
    if paging_cases:
        print(f'  paging/order cases (see PAGING_INTEGRITY verdict): {len(paging_cases)}')

    # Surface the paging verdicts on both sides; these should go FAIL -> PASS.
    for label, d in (('baseline', base), ('candidate', cand)):
        f = d / 'database.data.PAGING_INTEGRITY.json'
        if f.exists():
            v = json.loads(f.read_text())
            print(f'  feedback-database paging [{label:<9}]: {v.get("verdict")} '
                  f'(walked {v.get("distinct_rows_walked")}/{v.get("distinct_rows_expected")}, '
                  f'{v.get("duplicate_rows_across_pages")} duplicated)')
    for label, d in (('baseline', base), ('candidate', cand)):
        f = d / 'details.FULLSET.walk.json'
        if f.exists():
            v = json.loads(f.read_text())
            print(f'  feedback-details paging  [{label:<9}]: {v.get("verdict")} '
                  f'(reached {v.get("distinct_reached")}/{v.get("total_reported")}, '
                  f'{v.get("duplicates_across_pages")} duplicated)')

    if totals['REAL'] or only_base or only_cand:
        print(f'\n  FAIL: {len(real_cases)} case(s) with real differences: {real_cases}')
        return 1
    print('\n  PASS: no real data differences.')
    return 0


if __name__ == '__main__':
    sys.exit(main())
