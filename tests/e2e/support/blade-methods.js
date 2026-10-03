/**
 * Lift class methods out of a Blade template so e2e specs can run the real code.
 *
 * Copied from tests/e2e/calendar-weekend-columns.spec.js (skipLiteral / extractMethod),
 * with the template path passed in rather than read from a module-level constant.
 */
/**
 * Index of the closing quote of the string or template literal starting at `start`.
 * A `${ ... }` hole inside a template may itself contain braces, quotes and further
 * templates, so it is walked rather than scanned for the next backtick.
 */
function skipLiteral(source, start) {
  const quote = source[start];
  for (let i = start + 1; i < source.length; i += 1) {
    const ch = source[i];
    if (ch === "\\") { i += 1; continue; }
    if (ch === quote) return i;
    if (quote === "`" && ch === "$" && source[i + 1] === "{") {
      let depth = 1;
      i += 2;
      for (; i < source.length && depth > 0; i += 1) {
        const c = source[i];
        if (c === "\\") { i += 1; continue; }
        if (c === "'" || c === '"' || c === "`") { i = skipLiteral(source, i); continue; }
        if (c === "{") depth += 1;
        else if (c === "}") depth -= 1;
      }
      i -= 1;
    }
  }
  return source.length;
}

/**
 * Lift `name(args) { ... }` out of the template by matching braces.
 *
 * Comments and string/template literals are skipped, because a brace inside one is
 * punctuation and not structure. Counting them is not hypothetical: a comment in
 * ot-index.blade.php quoting `forEach(i => {` left the depth permanently one too high,
 * so extracting renderWeekCards() from that file ran past the end of the method and
 * returned a fragment that does not parse — with a syntax error that names neither the
 * comment nor the file.
 *
 * Known limit: a regular-expression literal is not tracked, so an unbalanced brace
 * inside one (`/\}/`) would still be counted. None of the extracted methods contains one.
 */
function extractMethod(source, name, file = "template") {
  // `async ` may precede the name: loadListView() is declared async, and without this
  // the extractor reports "method not found" for it - a failure that looks like a missing
  // method rather than an unsupported declaration form.
  const signature = new RegExp(`^[ \\t]*(?:async\\s+)?${name}\\s*\\(`, "m");
  const at = source.search(signature);
  if (at === -1) throw new Error(`Method ${name}() not found in ${file}`);

  const open = source.indexOf("{", at);
  if (open === -1) throw new Error(`Method ${name}() has no body`);

  let depth = 0;
  for (let i = open; i < source.length; i += 1) {
    const ch = source[i];
    const next = source[i + 1];

    if (ch === "/" && next === "/") {
      const eol = source.indexOf("\n", i);
      if (eol === -1) break;
      i = eol;
      continue;
    }
    if (ch === "/" && next === "*") {
      const end = source.indexOf("*/", i + 2);
      if (end === -1) break;
      i = end + 1;
      continue;
    }
    if (ch === "'" || ch === '"' || ch === "`") {
      i = skipLiteral(source, i);
      continue;
    }

    if (ch === "{") depth += 1;
    else if (ch === "}") {
      depth -= 1;
      if (depth === 0) return source.slice(at, i + 1);
    }
  }
  throw new Error(`Unbalanced braces in ${name}()`);
}

module.exports = { extractMethod, skipLiteral };
