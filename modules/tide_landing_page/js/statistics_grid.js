(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.hideParagraphs = {
    attach: function (context, settings) {
      const statisticsGridBlockCount = $('.paragraph-type--statistics-grid').find('tr[class*=draggable]').length;
      if(statisticsGridBlockCount) {
        $('.paragraph-type--statistics-grid').find('span[class*=paragraphs-badge]').html(statisticsGridBlockCount);
      }
    }
  };
})(jQuery, Drupal);
