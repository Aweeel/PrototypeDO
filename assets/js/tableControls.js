(function () {
    'use strict';

    const MIN_COLUMN_WIDTH = 72;
    const RESIZER_WIDTH = 6;

    function textOf(cell) {
        return (cell?.textContent || '').replace(/\s+/g, ' ').trim();
    }

    function compareValues(left, right) {
        const numericPattern = /^[+-]?(?:\d+\.?\d*|\.\d+)$/;
        const leftNumber = Number(left);
        const rightNumber = Number(right);
        if (numericPattern.test(left) && numericPattern.test(right)
            && Number.isFinite(leftNumber) && Number.isFinite(rightNumber)) {
            return leftNumber - rightNumber;
        }
        const leftDate = Date.parse(left);
        const rightDate = Date.parse(right);
        if (!Number.isNaN(leftDate) && !Number.isNaN(rightDate)) {
            return leftDate - rightDate;
        }
        return left.localeCompare(right, undefined, { numeric: true, sensitivity: 'base' });
    }

    function installStyles() {
        if (document.getElementById('table-controls-styles')) {
            return;
        }
        const style = document.createElement('style');
        style.id = 'table-controls-styles';
        style.textContent = `
            [data-table-controls] { table-layout: fixed !important; }
            [data-table-controls] th { position: relative; }
            [data-table-controls] td { white-space: nowrap; }
            [data-table-controls] th > .table-control-sort {
                display: block; width: 100%; padding: 0; border: 0; background: transparent;
                color: inherit; font: inherit; text-align: inherit; cursor: pointer;
            }
            [data-table-controls] th[data-fixed-column="true"] > .table-control-sort {
                cursor: default;
            }
            [data-table-controls] .table-control-resizer {
                position: absolute; z-index: 2; top: 0; right: 0; width: ${RESIZER_WIDTH}px;
                height: 100%; cursor: col-resize; touch-action: none; user-select: none;
                border-left: 1px solid rgb(209 213 219 / .7);
                background: rgb(229 231 235 / .7);
            }
            [data-table-controls] .table-control-resizer.is-resizing,
            [data-table-controls] .table-control-resizer:hover {
                background: rgb(59 130 246 / .8);
            }
            body.is-column-resizing { cursor: col-resize; user-select: none; }
        `;
        document.head.appendChild(style);
    }

    function ensureColumns(table, count) {
        let colgroup = table.querySelector(':scope > colgroup');
        if (!colgroup) {
            colgroup = document.createElement('colgroup');
            table.insertBefore(colgroup, table.firstChild);
        }
        while (colgroup.children.length < count) {
            colgroup.appendChild(document.createElement('col'));
        }
        while (colgroup.children.length > count) {
            colgroup.lastElementChild.remove();
        }
        return Array.from(colgroup.children).slice(0, count);
    }

    function measureText(text, reference) {
        const probe = document.createElement('span');
        const style = getComputedStyle(reference);
        probe.textContent = text;
        probe.style.cssText = 'position:absolute;left:-10000px;top:0;white-space:nowrap;visibility:hidden;';
        probe.style.font = style.font;
        probe.style.letterSpacing = style.letterSpacing;
        document.body.appendChild(probe);
        const width = probe.getBoundingClientRect().width;
        probe.remove();
        return width;
    }

    function getMinimumWidth(table, header, index) {
        const configuredMinimum = Number(header.dataset.minWidth);
        if (Number.isFinite(configuredMinimum) && configuredMinimum > 0) {
            return Math.max(MIN_COLUMN_WIDTH, configuredMinimum);
        }
        const style = getComputedStyle(header);
        const horizontalPadding = parseFloat(style.paddingLeft || '0')
            + parseFloat(style.paddingRight || '0');
        const headerWidth = measureText(textOf(header), header) + horizontalPadding + RESIZER_WIDTH + 12;
        const cellWidth = Array.from(table.tBodies[0]?.rows || [])
            .map((row) => row.cells[index])
            .filter(Boolean)
            .reduce((maximum, cell) => {
                return Math.max(maximum, measureText(textOf(cell), cell) + horizontalPadding + 12);
            }, 0);
        return Math.max(MIN_COLUMN_WIDTH, Math.min(520, Math.max(cellWidth, headerWidth)));
    }

    function expandToAvailableWidth(table, widths, fixedColumns) {
        const availableWidth = table.parentElement?.clientWidth || 0;
        const currentWidth = widths.reduce((total, width) => total + width, 0);
        const expandableColumns = fixedColumns
            .map((fixed, index) => fixed ? -1 : index)
            .filter((index) => index >= 0);
        if (availableWidth > currentWidth && expandableColumns.length > 0) {
            const extraWidth = (availableWidth - currentWidth) / expandableColumns.length;
            expandableColumns.forEach((index) => {
                widths[index] += extraWidth;
            });
        }
    }

    function fitToAvailableWidth(table, widths, minimums, fixedColumns) {
        const availableWidth = table.parentElement?.clientWidth || 0;
        const currentWidth = widths.reduce((total, width) => total + width, 0);
        if (availableWidth >= currentWidth) {
            return;
        }

        const shrinkableColumns = fixedColumns
            .map((fixed, index) => fixed ? -1 : index)
            .filter((index) => index >= 0 && widths[index] > minimums[index]);
        let remainingWidth = currentWidth - availableWidth;
        while (remainingWidth > 0 && shrinkableColumns.length > 0) {
            const totalCapacity = shrinkableColumns.reduce(
                (total, index) => total + widths[index] - minimums[index],
                0
            );
            if (totalCapacity <= 0) {
                break;
            }
            shrinkableColumns.forEach((index) => {
                const capacity = widths[index] - minimums[index];
                widths[index] -= Math.min(capacity, remainingWidth * capacity / totalCapacity);
            });
            remainingWidth = widths.reduce((total, width) => total + width, 0) - availableWidth;
            shrinkableColumns.splice(
                0,
                shrinkableColumns.length,
                ...shrinkableColumns.filter((index) => widths[index] > minimums[index] + 0.01)
            );
        }
        if (remainingWidth > 0) {
            const lastColumn = shrinkableColumns[shrinkableColumns.length - 1];
            if (lastColumn !== undefined) {
                widths[lastColumn] = Math.max(minimums[lastColumn], widths[lastColumn] - remainingWidth);
            }
        }
    }

    function installTable(table) {
        const headerRow = table.tHead?.rows[0];
        if (!headerRow) {
            return;
        }
        const headers = Array.from(headerRow.cells);
        const sortOnly = table.hasAttribute('data-table-sort-only');
        const columns = ensureColumns(table, headers.length);
        const minimums = headers.map((header, index) => getMinimumWidth(table, header, index));
        const widths = columns.map((column, index) => Math.max(
            parseFloat(column.style.width) || headers[index].getBoundingClientRect().width || MIN_COLUMN_WIDTH,
            minimums[index]
        ));
        const fixedColumns = headers.map((header) => header.dataset.fixedColumn === 'true'
            || (header.dataset.resizable !== 'true' && /^(actions?|select)$/i.test(textOf(header))));
        if (!sortOnly) {
            fitToAvailableWidth(table, widths, minimums, fixedColumns);
            expandToAvailableWidth(table, widths, fixedColumns);
        }

        headers.forEach((header, index) => {
            const fixed = fixedColumns[index];
            if (fixed) {
                header.dataset.fixedColumn = 'true';
            }
            header.dataset.tableControlReady = 'true';
            if (fixed) {
                header.querySelector('.table-control-sort')?.remove();
                header.querySelector('.table-control-sort-indicator')?.remove();
                return;
            }
            if (!header.querySelector('.table-control-sort')) {
                const content = Array.from(header.childNodes);
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'table-control-sort';
                button.setAttribute('aria-label', `Sort by ${textOf(header)}`);
                content.forEach((node) => button.appendChild(node));
                const indicator = document.createElement('span');
                indicator.className = 'table-control-sort-indicator';
                indicator.textContent = ' ↕';
                button.appendChild(indicator);
                button.addEventListener('click', () => {
                        const direction = header.dataset.sortDirection === 'ascending'
                            ? 'descending' : 'ascending';
                        table.dispatchEvent(new CustomEvent('table:sort', {
                            bubbles: true,
                            detail: { columnIndex: index, direction }
                        }));
                        headers.forEach((item) => {
                            item.dataset.sortDirection = 'none';
                            item.querySelector('.table-control-sort-indicator')?.replaceChildren(
                                document.createTextNode(' ↕')
                            );
                        });
                        header.dataset.sortDirection = direction;
                        indicator.textContent = direction === 'ascending' ? ' ↑' : ' ↓';
                        if (table.dataset.sortSource
                            && !['matrix'].includes(table.dataset.sortSource)) {
                            return;
                        }
                        const bodyRows = Array.from(table.tBodies[0]?.rows || [])
                            .filter((row) => row.cells.length === headers.length);
                        bodyRows.sort((left, right) => {
                            const result = compareValues(
                                textOf(left.cells[index]),
                                textOf(right.cells[index])
                            );
                            return direction === 'ascending' ? result : -result;
                        });
                        bodyRows.forEach((row) => table.tBodies[0].appendChild(row));
                });
                header.replaceChildren(button);
            }
        });

        headers.forEach((header, index) => {
            const next = headers[index + 1];
            if (sortOnly || header.dataset.fixedColumn === 'true'
                || !next
                || next.dataset.fixedColumn === 'true'
                || header.querySelector('.table-control-resizer')) {
                return;
            }
            const handle = document.createElement('span');
            handle.className = 'table-control-resizer';
            handle.setAttribute('aria-hidden', 'true');
            handle.addEventListener('pointerdown', (event) => {
                event.preventDefault();
                event.stopPropagation();
                const startX = event.clientX;
                const leftStart = widths[index];
                const rightStart = widths[index + 1];
                const pairTotal = leftStart + rightStart;
                handle.setPointerCapture(event.pointerId);
                handle.classList.add('is-resizing');
                document.body.classList.add('is-column-resizing');
                const move = (moveEvent) => {
                    const delta = moveEvent.clientX - startX;
                    const left = Math.min(
                        Math.max(leftStart + delta, minimums[index]),
                        pairTotal - minimums[index + 1]
                    );
                    widths[index] = left;
                    widths[index + 1] = pairTotal - left;
                    columns[index].style.width = `${widths[index]}px`;
                    columns[index + 1].style.width = `${widths[index + 1]}px`;
                };
                const stop = () => {
                    if (handle.hasPointerCapture(event.pointerId)) {
                        handle.releasePointerCapture(event.pointerId);
                    }
                    handle.classList.remove('is-resizing');
                    document.body.classList.remove('is-column-resizing');
                    handle.removeEventListener('pointermove', move);
                    handle.removeEventListener('pointerup', stop);
                    handle.removeEventListener('pointercancel', stop);
                };
                handle.addEventListener('pointermove', move);
                handle.addEventListener('pointerup', stop);
                handle.addEventListener('pointercancel', stop);
            });
            header.appendChild(handle);
        });

        if (!sortOnly) {
            widths.forEach((width, index) => {
                columns[index].style.width = `${width}px`;
            });
            const tableWidth = widths.reduce((total, width) => total + width, 0);
            table.style.width = `${tableWidth}px`;
            table.style.minWidth = `${tableWidth}px`;
        }

        if (!table.dataset.tableControlObserver) {
            table.dataset.tableControlObserver = 'true';
            const bodyObserver = new MutationObserver(() => {
                window.clearTimeout(table.tableControlRefresh);
                table.tableControlRefresh = window.setTimeout(() => {
                    headers.forEach((header, index) => {
                        const minimum = getMinimumWidth(table, header, index);
                        if (widths[index] < minimum) {
                            widths[index] = minimum;
                            columns[index].style.width = `${minimum}px`;
                        }
                    });
                    if (!sortOnly) {
                        fitToAvailableWidth(table, widths, minimums, fixedColumns);
                        expandToAvailableWidth(table, widths, fixedColumns);
                        const tableWidth = widths.reduce((total, width) => total + width, 0);
                        table.style.width = `${tableWidth}px`;
                        table.style.minWidth = `${tableWidth}px`;
                    }
                }, 0);
            });
            Array.from(table.tBodies).forEach((body) => bodyObserver.observe(body, { childList: true }));
        }
    }

    function initialize() {
        installStyles();
        document.querySelectorAll('table[data-table-controls]').forEach((table) => {
            installTable(table);
            const header = table.tHead;
            if (!header || header.dataset.tableControlObserver === 'true') {
                return;
            }
            header.dataset.tableControlObserver = 'true';
            const observer = new MutationObserver(() => {
                window.clearTimeout(table.tableControlRefresh);
                table.tableControlRefresh = window.setTimeout(() => installTable(table), 0);
            });
            observer.observe(header, { childList: true, subtree: true });
        });
    }

    let resizeRefresh;
    window.addEventListener('resize', () => {
        window.clearTimeout(resizeRefresh);
        resizeRefresh = window.setTimeout(() => {
            document.querySelectorAll('table[data-table-controls]:not([data-table-sort-only])')
                .forEach((table) => installTable(table));
        },  frameDelay());
    });

    function frameDelay() {
        return typeof window.requestAnimationFrame === 'function' ? 16 : 0;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
})();
