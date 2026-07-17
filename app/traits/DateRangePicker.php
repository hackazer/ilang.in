<?php

namespace Traits;

use Core\View;

trait DateRangePicker
{
    protected function loadDateRangePicker(?string $minDate = null): void
    {
        \Helpers\CDN::load('airdatepicker');

        $options = [
            'applyLabel' => e('Apply'),
            'cancelLabel' => e('Cancel'),
            'rangeLabels' => [
                'last7' => e('Last 7 Days'),
                'last30' => e('Last 30 Days'),
                'thisMonth' => e('This Month'),
                'lastMonth' => e('Last Month'),
                'last3Months' => e('Last 3 Months'),
            ],
            'locale' => [
                'days' => [e('Sunday'), e('Monday'), e('Tuesday'), e('Wednesday'), e('Thursday'), e('Friday'), e('Saturday')],
                'daysShort' => [e('Su'), e('Mo'), e('Tu'), e('We'), e('Th'), e('Fr'), e('Sa')],
                'daysMin' => [e('Su'), e('Mo'), e('Tu'), e('We'), e('Th'), e('Fr'), e('Sa')],
                'months' => [e('January'), e('February'), e('March'), e('April'), e('May'), e('June'), e('July'), e('August'), e('September'), e('October'), e('November'), e('December')],
                'monthsShort' => [e('Jan'), e('Feb'), e('Mar'), e('Apr'), e('May'), e('Jun'), e('Jul'), e('Aug'), e('Sep'), e('Oct'), e('Nov'), e('Dec')],
                'today' => e('Today'),
                'clear' => e('Clear'),
                'dateFormat' => 'MM/dd/yyyy',
                'timeFormat' => 'HH:mm',
                'firstDay' => 0,
            ],
        ];

        if ($minDate) $options['minDate'] = $minDate;

        View::push(
            '<script>document.addEventListener("DOMContentLoaded", function(){AppDatePicker.initRange("input[name=customreport]", '.json_encode($options, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT).');});</script>',
            'custom'
        )->toFooter();
    }
}
