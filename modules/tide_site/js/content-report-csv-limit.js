/**
 * @file
 * Content Report CSV Export Limiter
 * 
 * Limits CSV export functionality when pagination exceeds the maximum allowed pages.
 * When the total number of pages or current page exceeds 100, this behavior:
 * - Hides the CSV export link
 * - Displays a warning message instructing users to apply filters
 * - Provides guidance on exporting multiple smaller CSV files
 * 
 * This prevents server overload and timeout issues when exporting large datasets.
 */
(function (Drupal, once) {
  Drupal.behaviors.contentReportCsvLimit = {
    attach: function (context) {
      once('content-report-csv-limit', '.csv-feed', context)
        .forEach(function (csvFeed) {
          var viewEl = csvFeed.closest('.view');
          if (!viewEl) {
            return;
          }

          var pager = viewEl.querySelector('.pager');
          if (!pager) {
            return;
          }

          /**
           * Extracts the page index from a pagination URL.
           */
          function getPageIndexFromHref(href) {
            if (!href) {
              return null;
            }
            var match = href.match(/[?&]page=(\d+)/);
            if (!match || !match[1]) {
              return null;
            }
            var idx = parseInt(match[1], 10);
            return isNaN(idx) ? null : idx;
          }

          // Find the maximum page index from pager elements.
          var maxPageIndex = null;

          // Check the "last" link first
          var lastLink = pager.querySelector('.pager__item--last a');
          if (lastLink) {
            maxPageIndex = getPageIndexFromHref(lastLink.getAttribute('href'));
          }

          // Scan all pagination links to find the highest page index.
          var links = pager.querySelectorAll('a[href*="page="]');
          links.forEach(function (a) {
            var idx = getPageIndexFromHref(a.getAttribute('href'));
            if (idx !== null && (maxPageIndex === null || idx > maxPageIndex)) {
              maxPageIndex = idx;
            }
          });

          // Determine the current page index from URL.
          var currentPageIndex = 0;
          var locMatch = window.location.search.match(/[?&]page=(\d+)/);
          if (locMatch && locMatch[1]) {
            var tmp = parseInt(locMatch[1], 10);
            if (!isNaN(tmp)) {
              currentPageIndex = tmp;
            }
          }

          // Ensure maxPageIndex accounts for the current page.
          if (maxPageIndex === null || currentPageIndex > maxPageIndex) {
            maxPageIndex = currentPageIndex;
          }

          // Calculate total pages (page index is 0-based).
          var totalPages = maxPageIndex + 1;
          console.log('[content_report_csv_limit] totalPages =', totalPages);

          // Maximum allowed pages for CSV export.
          var maxAllowedPages = 100;

          // Check if limits are exceeded.
          if (totalPages > maxAllowedPages || (currentPageIndex + 1) > maxAllowedPages) {
            // Hide the CSV export link.
            var link = csvFeed.querySelector('a.feed-icon');
            if (link) {
              link.style.display = 'none';
            }

            // Create and display warning message.
            var warning = document.createElement('div');
            warning.className = 'csv-feed-warning';
            warning.innerHTML =
              '<p>Please narrow down the results using the filters before exporting the CSV (maximum '
              + maxAllowedPages +
              ' pagination per export).</p>' +
              '<p>If one export is not enough, you can apply additional filters and export multiple smaller CSV files, then combine them in Excel.</p>';

            // Insert warning message in an appropriate location.
            var filters = viewEl.querySelector('.view-filters');
            var content = viewEl.querySelector('.view-content');

            if (filters && content && filters.parentNode) {
              filters.parentNode.insertBefore(warning, content);
            }
            else if (filters && filters.parentNode) {
              filters.parentNode.insertBefore(warning, filters.nextSibling);
            }
            else {
              csvFeed.appendChild(warning);
            }

          }
        });
    }
  };

})(Drupal, once);
