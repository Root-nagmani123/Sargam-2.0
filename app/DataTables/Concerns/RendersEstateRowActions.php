<?php

namespace App\DataTables\Concerns;

/**
 * Shared renderers for the estate listings' Action column and status pills.
 *
 * The estate screens all use the same row-action language: a stacked material
 * icon + caption, greyed rather than removed when the action is unavailable so
 * the Action column keeps one shape down the whole table. Keeping the markup in
 * one place stops the pages drifting apart as they are modernised one by one.
 *
 * Styling lives in public/css/estate-request-admin.css (.rfe-action*, .rfe-status*).
 */
trait RendersEstateRowActions
{
    /**
     * One row action.
     *
     * @param  string  $icon     material-symbols ligature name
     * @param  string  $label    caption under the icon
     * @param  string  $tone     colour key — view|edit|change|delete|possession|return|approve|reject
     * @param  array{href?:string,class?:string,title?:string,attrs?:string,disabled?:bool,static?:bool}  $options
     */
    protected static function actionLink(string $icon, string $label, string $tone, array $options = []): string
    {
        $title = e($options['title'] ?? $label);
        $isDisabled = ! empty($options['disabled']);
        $isStatic = $isDisabled || ! empty($options['static']);

        $classes = 'rfe-action rfe-action--' . $tone . ($isDisabled ? ' rfe-action--disabled' : '');
        if (! empty($options['class'])) {
            $classes .= ' ' . $options['class'];
        }

        $inner = '<i class="material-icons material-symbols-rounded" aria-hidden="true">' . $icon . '</i>'
            . '<span class="rfe-action-label">' . e($label) . '</span>';

        if ($isStatic) {
            return '<span class="' . $classes . '" title="' . $title . '" aria-disabled="true">' . $inner . '</span>';
        }

        $href = $options['href'] ?? 'javascript:void(0);';
        $extra = ! empty($options['attrs']) ? ' ' . $options['attrs'] : '';

        return '<a href="' . e($href) . '" class="' . $classes . '" title="' . $title . '"'
            . ' aria-label="' . $title . '"' . $extra . '>' . $inner . '</a>';
    }

    /** Soft status pill (matching .programme-status-badge sizing, estate tones). */
    protected static function statusBadge(string $label, string $tone): string
    {
        return '<span class="badge rounded-1 programme-status-badge rfe-status rfe-status--' . $tone . '">'
            . e($label) . '</span>';
    }

    /**
     * "Name — muted employee id", the shared Name & ID cell.
     */
    protected static function nameWithId(?string $name, ?string $employeeId): string
    {
        $name = trim((string) $name);
        $employeeId = trim((string) $employeeId);

        if ($name === '' && $employeeId === '') {
            return '—';
        }

        $html = $name !== '' ? '<span class="rfe-name">' . e($name) . '</span>' : '';
        if ($employeeId !== '') {
            $html .= ($html !== '' ? ' ' : '') . '<span class="rfe-emp-id">' . ($html !== '' ? '- ' : '') . e($employeeId) . '</span>';
        }

        return $html;
    }
}
