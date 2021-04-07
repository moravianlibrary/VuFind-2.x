/*global VuFind */
function loadCoverByElement(data, element) {
  var url = VuFind.path + '/AJAX/JSON?method=' + 'getRecordCover';
  var img = element.find('img');
  var spinner = element.find('div.spinner');
  var container = element.find('div.cover-container');
  var source = $('<p class="cover-source">' + VuFind.translate('cover_source_label') + ' </p>');
  function coverCallback(response) {
    if (typeof response.data.url !== 'undefined' && response.data.url !== false) {
      img.attr("src", response.data.url);
      var inlink = element.parent().is('a.record-link');
      var medium = img.parents('.media-left, .media-right, .carousel-item');
      if (typeof response.data.backlink_text !== 'undefined') {
        if (typeof response.data.backlink_url !== 'undefined') {
          var link = $('<a href="' + response.data.backlink_url + '" class="cover-backlink" target="_blank">' + response.data.backlink_text + '</a>');
          source.append(link);
        } else {
          var span = $('<span class="cover-source-text"' + response.data.backlink_text + '</span>');
          source.append(span);
        }
        if (inlink === true && response.data.backlink_on_results === true) {
          medium.append(source);
        }
      }
      if (inlink === true) {
        img.detach();
        medium.children('a').prepend(img);
        container.parents('.ajaxcover').remove();
      } else if ($('.cover-source').length === 0) {
        container.append(source);
      }
    } else {
      img.remove();
      source.remove();
      if (typeof response.data.html !== 'undefined') {
        container.html(response.data.html);
      } else {
        container.html();
      }
    }
    spinner.hide();
    container.show();
  }
  $.ajax({
    dataType: "json",
    url: url,
    method: "GET",
    data: data,
    element: element,
    success: coverCallback
  });
}

function loadCovers() {
  $('.ajaxcover').each(function getDataAndLoadCovers() {
    var img = $(this).find('img');
    var data = {
      source: img.data('recordsource'),
      recordId: img.data('recordid'),
      size: img.data('coversize')
    };
    loadCoverByElement(data, $(this));
  });
}
$(document).ready(loadCovers);
