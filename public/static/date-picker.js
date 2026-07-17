(function (root, factory) {
    var api = factory(root);
    if (typeof module === 'object' && module.exports) module.exports = api;
    root.AppDatePicker = api;
}(typeof window !== 'undefined' ? window : globalThis, function (root) {
    'use strict';

    var defaultLocale = {
        days: ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        daysShort: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        daysMin: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
        months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        monthsShort: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        today: 'Today',
        clear: 'Clear',
        dateFormat: 'yyyy-MM-dd',
        timeFormat: 'HH:mm',
        firstDay: 0
    };

    function pad(value) { return String(value).padStart(2, '0'); }

    function format(date, pattern) {
        if (!(date instanceof Date) || Number.isNaN(date.getTime())) return '';
        var parts = {
            yyyy: String(date.getFullYear()),
            MM: pad(date.getMonth() + 1),
            dd: pad(date.getDate())
        };
        return String(pattern || 'yyyy-MM-dd').replace(/yyyy|MM|dd/g, function (token) { return parts[token]; });
    }

    function startOfMonth(date, offset) {
        return new Date(date.getFullYear(), date.getMonth() + (offset || 0), 1, 12);
    }

    function endOfMonth(date, offset) {
        return new Date(date.getFullYear(), date.getMonth() + (offset || 0) + 1, 0, 12);
    }

    function daysAgo(date, count) {
        var result = new Date(date.getFullYear(), date.getMonth(), date.getDate(), 12);
        result.setDate(result.getDate() - count);
        return result;
    }

    function buildRanges(now, labels) {
        labels = labels || {};
        return [
            { label: labels.last7 || 'Last 7 Days', dates: [daysAgo(now, 6), daysAgo(now, 0)] },
            { label: labels.last30 || 'Last 30 Days', dates: [daysAgo(now, 29), daysAgo(now, 0)] },
            { label: labels.thisMonth || 'This Month', dates: [startOfMonth(now, 0), endOfMonth(now, 0)] },
            { label: labels.lastMonth || 'Last Month', dates: [startOfMonth(now, -1), endOfMonth(now, -1)] },
            { label: labels.last3Months || 'Last 3 Months', dates: [startOfMonth(now, -2), daysAgo(now, 0)] }
        ];
    }

    function resolve(target) {
        if (typeof target === 'string') return root.document.querySelector(target);
        if (target && target.jquery) return target[0];
        return target;
    }

    function localDate(value) {
        if (value instanceof Date) return value;
        var match = String(value || '').match(/^(\d{4})-(\d{2})-(\d{2})/);
        return match ? new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]), 12) : null;
    }

    function event(name, detail) {
        if (typeof root.CustomEvent === 'function') return new root.CustomEvent(name, { bubbles: true, detail: detail });
        var result = root.document.createEvent('CustomEvent');
        result.initCustomEvent(name, true, false, detail);
        return result;
    }

    function init(target, options) {
        options = options || {};
        var input = resolve(target);
        if (!input || typeof root.AirDatepicker !== 'function') return null;
        if (input.__appDatePicker) return input.__appDatePicker;

        var selected = localDate(input.value);
        if (!selected && options.autoPick) {
            selected = new Date();
            input.value = format(selected, options.dateFormat || 'yyyy-MM-dd');
        }

        var picker = new root.AirDatepicker(input, {
            autoClose: true,
            dateFormat: options.dateFormat || 'yyyy-MM-dd',
            selectedDates: selected ? [selected] : [],
            minDate: options.minDate,
            maxDate: options.maxDate,
            locale: options.locale || defaultLocale,
            onSelect: function (selection) {
                input.value = format(selection.date, options.dateFormat || 'yyyy-MM-dd');
                input.dispatchEvent(event('change', { date: selection.date }));
                if (typeof options.onSelect === 'function') options.onSelect.call(input, selection.date, picker);
            }
        });
        input.__appDatePicker = picker;
        return picker;
    }

    function initAll(rootElement) {
        var scope = rootElement || root.document;
        Array.from(scope.querySelectorAll('[data-toggle="datetimepicker"], [data-toggle="datepicker"], [data-datepicker]')).forEach(function (input) {
            init(input, { autoPick: input.matches('[data-toggle="datetimepicker"]'), dateFormat: 'yyyy-MM-dd' });
        });
    }

    function set(target, value) {
        var input = resolve(target);
        var picker = init(input, { dateFormat: 'yyyy-MM-dd' });
        var date = localDate(value);
        if (!picker || !date) return picker;
        picker.selectDate(date);
        input.value = format(date, 'yyyy-MM-dd');
        return picker;
    }

    function initRange(target, options) {
        options = options || {};
        var input = resolve(target);
        if (!input || typeof root.AirDatepicker !== 'function') return null;
        if (input.__appDateRangePicker) return input.__appDateRangePicker;

        var now = options.now || new Date();
        var minimum = localDate(options.minDate);
        var committed = (options.selectedDates || [daysAgo(now, 14), daysAgo(now, 0)]).slice();
        if (minimum && committed[0] < minimum) committed[0] = minimum;
        var ranges = buildRanges(now, options.rangeLabels).map(function (range) {
            if (minimum && range.dates[0] < minimum) range.dates[0] = minimum;
            return range;
        });
        var picker;

        function write(dates) {
            if (!dates || dates.length < 2) return;
            input.value = format(dates[0], 'MM/dd/yyyy') + ' - ' + format(dates[1], 'MM/dd/yyyy');
        }

        function apply() {
            var dates = picker.selectedDates.slice(0, 2);
            if (dates.length < 2) return;
            committed = dates.slice();
            write(committed);
            input.dispatchEvent(event('app:date-range-apply', {
                start: format(committed[0], 'MM/dd/yyyy'),
                end: format(committed[1], 'MM/dd/yyyy'),
                startDate: committed[0],
                endDate: committed[1]
            }));
            picker.hide();
        }

        var buttons = ranges.map(function (range) {
            return {
                content: range.label,
                className: 'air-datepicker-button app-date-range-preset',
                onClick: function (instance) { instance.selectDate(range.dates, { silent: true }); }
            };
        });
        buttons.push({ content: options.cancelLabel || 'Cancel', className: 'air-datepicker-button', onClick: function (instance) {
            instance.selectDate(committed, { silent: true });
            write(committed);
            instance.hide();
        }});
        buttons.push({ content: options.applyLabel || 'Apply', className: 'air-datepicker-button -primary-', onClick: apply });

        picker = new root.AirDatepicker(input, {
            range: true,
            multipleDatesSeparator: ' - ',
            dateFormat: 'MM/dd/yyyy',
            selectedDates: committed,
            maxDate: options.maxDate || now,
            minDate: minimum || options.minDate,
            locale: options.locale || defaultLocale,
            buttons: buttons,
            autoClose: false
        });
        write(committed);
        input.__appDateRangePicker = picker;
        return picker;
    }

    return { init: init, initAll: initAll, initRange: initRange, set: set, format: format, buildRanges: buildRanges };
}));
