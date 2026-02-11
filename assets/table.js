document.addEventListener('DOMContentLoaded', function () {
    var tables = document.querySelectorAll('table.data-table');
    if (!tables.length) return;

    function parseDate(value) {
        var trimmed = value.trim();
        var iso = /^(\d{4})-(\d{2})-(\d{2})$/.exec(trimmed);
        if (iso) {
            return new Date(iso[1], iso[2] - 1, iso[3]).getTime();
        }
        var dmy = /^(\d{2})\s*\/\s*(\d{2})\s*\/\s*(\d{4})$/.exec(trimmed);
        if (dmy) {
            return new Date(dmy[3], dmy[2] - 1, dmy[1]).getTime();
        }
        return null;
    }

    function parseValue(value) {
        var trimmed = value.trim();
        var dateValue = parseDate(trimmed);
        if (dateValue !== null) {
            return { type: 'date', value: dateValue };
        }
        var numeric = trimmed.replace(/[^0-9.\-]/g, '');
        if (numeric && !isNaN(numeric)) {
            return { type: 'number', value: parseFloat(numeric) };
        }
        return { type: 'text', value: trimmed.toLowerCase() };
    }

    tables.forEach(function (table) {
        var tbody = table.tBodies[0];
        if (!tbody) return;
        var allRows = Array.from(tbody.rows);
        if (!allRows.length) return;

        var pageSizes = [7, 10, 25, 50];
        var defaultSize = parseInt(table.dataset.pageSize || '7', 10);
        var pageSize = pageSizes.indexOf(defaultSize) >= 0 ? defaultSize : 7;
        var currentPage = 1;
        var sortIndex = null;
        var sortDir = 'asc';
        var searchTerm = '';

        var wrap = table.closest('.table-wrap') || table.parentElement;
        if (!wrap) return;

        var controls = document.createElement('div');
        controls.className = 'table-controls';
        controls.innerHTML = '' +
            '<div class="table-controls-left">' +
            '  <label>Show ' +
            '    <select class="table-page-size"></select> entries' +
            '  </label>' +
            '</div>' +
            '<div class="table-controls-right">' +
            '  <label>Search: ' +
            '    <input type="search" class="table-search" placeholder="">' +
            '  </label>' +
            '</div>';

        var pageSizeSelect = controls.querySelector('.table-page-size');
        pageSizes.forEach(function (size) {
            var option = document.createElement('option');
            option.value = size;
            option.textContent = size;
            if (size === pageSize) option.selected = true;
            pageSizeSelect.appendChild(option);
        });

        var searchInput = controls.querySelector('.table-search');

        var footer = document.createElement('div');
        footer.className = 'table-footer';
        footer.innerHTML = '<div class="table-info"></div><div class="table-pager"></div>';
        var infoEl = footer.querySelector('.table-info');
        var pagerEl = footer.querySelector('.table-pager');

        wrap.insertBefore(controls, table);
        wrap.appendChild(footer);

        var headers = [];
        if (table.tHead && table.tHead.rows.length) {
            headers = Array.from(table.tHead.rows[0].cells);
        }
        headers.forEach(function (th, idx) {
            th.classList.add('sortable');
            th.addEventListener('click', function () {
                if (sortIndex === idx) {
                    sortDir = sortDir === 'asc' ? 'desc' : 'asc';
                } else {
                    sortIndex = idx;
                    sortDir = 'asc';
                }
                headers.forEach(function (header, hIdx) {
                    header.classList.remove('sorted-asc', 'sorted-desc');
                    if (hIdx === sortIndex) {
                        header.classList.add(sortDir === 'asc' ? 'sorted-asc' : 'sorted-desc');
                    }
                });
                currentPage = 1;
                render();
            });
        });

        function getFilteredRows() {
            if (!searchTerm) return allRows.slice();
            return allRows.filter(function (row) {
                return row.textContent.toLowerCase().indexOf(searchTerm) !== -1;
            });
        }

        function getSortedRows(rows) {
            if (sortIndex === null) return rows;
            return rows.slice().sort(function (a, b) {
                var aValue = parseValue(a.cells[sortIndex]?.textContent || '');
                var bValue = parseValue(b.cells[sortIndex]?.textContent || '');
                var result = 0;
                if (aValue.type === bValue.type) {
                    if (aValue.value < bValue.value) result = -1;
                    if (aValue.value > bValue.value) result = 1;
                } else {
                    result = aValue.type.localeCompare(bValue.type);
                }
                return sortDir === 'asc' ? result : -result;
            });
        }

        function renderPager(totalRows) {
            var totalPages = Math.max(1, Math.ceil(totalRows / pageSize));
            currentPage = Math.min(currentPage, totalPages);
            var start = (currentPage - 1) * pageSize + 1;
            var end = Math.min(currentPage * pageSize, totalRows);
            infoEl.textContent = totalRows
                ? 'Showing ' + start + ' to ' + end + ' of ' + totalRows + ' entries'
                : 'Showing 0 entries';

            pagerEl.innerHTML = '';
            var createBtn = function (label, page, disabled) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'page-btn' + (disabled ? ' disabled' : '');
                btn.textContent = label;
                if (!disabled) {
                    btn.addEventListener('click', function () {
                        currentPage = page;
                        render();
                    });
                }
                return btn;
            };

            pagerEl.appendChild(createBtn('‹', currentPage - 1, currentPage <= 1));
            var maxButtons = 5;
            var startPage = Math.max(1, currentPage - 2);
            var endPage = Math.min(totalPages, startPage + maxButtons - 1);
            if (endPage - startPage < maxButtons - 1) {
                startPage = Math.max(1, endPage - maxButtons + 1);
            }
            for (var i = startPage; i <= endPage; i++) {
                var btn = createBtn(String(i), i, false);
                if (i === currentPage) btn.classList.add('active');
                pagerEl.appendChild(btn);
            }
            pagerEl.appendChild(createBtn('›', currentPage + 1, currentPage >= totalPages));
        }

        function render() {
            searchTerm = searchInput.value.trim().toLowerCase();
            var filtered = getFilteredRows();
            var sorted = getSortedRows(filtered);
            var totalRows = sorted.length;
            var startIndex = (currentPage - 1) * pageSize;
            var pageRows = sorted.slice(startIndex, startIndex + pageSize);
            tbody.innerHTML = '';
            pageRows.forEach(function (row) {
                tbody.appendChild(row);
            });
            renderPager(totalRows);
        }

        pageSizeSelect.addEventListener('change', function () {
            pageSize = parseInt(pageSizeSelect.value, 10);
            currentPage = 1;
            render();
        });
        searchInput.addEventListener('input', function () {
            currentPage = 1;
            render();
        });

        render();
    });
});
