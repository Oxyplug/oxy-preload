jQuery(document).ready(function ($) {

  $('.oxyplug-preload-has-tooltip').on('mouseenter', function () {
    const tooltip_text = $(this).data('tooltip').trim();
    if (tooltip_text.length > 0) {
      // Link
      let link = '';
      let href = $(this).data('href');
      let href_text = $(this).data('href-text');
      if (href && href_text) {
        link = ` <a href="${href.trim()}" target="_blank">${href_text.trim()}</a>`;
      }

      $(this).prepend('<div class="oxyplug-preload-tooltip">' + tooltip_text + link + '</div>');
      $(this).find('.oxyplug-preload-tooltip').css('top', $(this).height() + 5).fadeIn();
    }
  }).on('mouseleave', function () {
    $(this).find('.oxyplug-preload-tooltip').fadeOut(function () {
      $(this).remove();
    });
  });

  $(document).on('click', '.oxyplug-preload-add-more', function () {
    const $new_el = $(this).prev().clone(true);
    $new_el.find('md-outlined-text-field').val('');
    $new_el.find('.oxyplug-preload-remove-url').show();
    $(this).before($new_el);
  });

  $(document).on('click', 'md-outlined-text-field.has-clear-button md-icon-button', function () {
    $(this).parent().val('').trigger('input');
  });

  $(document).on('click', '.oxyplug-preload-remove-url', function () {
    const name_attr = $(this).prev().attr('name').trim();
    $(this).parent().remove();

    const inputs = $(`[name="${name_attr}"]`);
    if (inputs.length === 1) {
      inputs[0].error = false;
      inputs[0].errorText = '';
      $(inputs[0]).removeClass('oxyplug-preload-invalid');
    }
  });

  $(document).on('change', '.oxyplug-preload-switch-wrap md-switch', function () {
    const $text_el = $(this).next('span');
    if (this.selected) {
      $text_el.addClass('active');
    } else {
      $text_el.removeClass('active');
    }
  });

  const check_url_validity = function (ele) {
    let url_regex = /^https?:\/\/([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(\/[-a-zA-Z0-9@:%._+~#=]*)*(\?[a-zA-Z0-9=&._-]*)?$/;
    const inputs_count = $(`[name="${$(ele).attr('name').trim()}"`).length;
    if (inputs_count === 1) {
      url_regex = /^(https?:\/\/([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(\/[-a-zA-Z0-9@:%._+~#=]*)*(\?[a-zA-Z0-9=&._-]*)?)?$/;
    }

    const value = $(ele).val().trim();
    if (url_regex.test(value)) {
      ele.error = false;
      ele.errorText = '';
      $(ele).removeClass('oxyplug-preload-invalid');
    } else {
      ele.error = true;
      ele.errorText = oxyplug_preload_defines.trans.invalid_url;
      $(ele).addClass('oxyplug-preload-invalid');
    }
  }

  $(document).on('input', '[name^="preloads"]', function () {
    check_url_validity(this);
  });

  $(document).on('click', '#oxyplug-preload-save-preloads', function () {
    const $this = $(this);
    const types = ['script', 'style', 'font'];
    const data = [
      {name: 'action', value: 'oxyplug_preload_save_preloads'},
      {name: 'oxyplug_preload_save_preloads_nonce', value: $(this).attr('data-nonce')}
    ];

    // Featured image preload
    const $featured_image_preload = $('[name="featured_image_preload"]');
    if ($featured_image_preload[0].selected) {
      data.push({name: $featured_image_preload.attr('name'), value: 'on'});
    }

    // CSS/JS/Font preload
    types.forEach(type => {
      $(`[name="preloads[${type}][]"]`).each(function () {
        check_url_validity(this);
        data.push({
          name: $(this).attr('name'),
          value: $(this).val().trim()
        });
      });
    });

    if ($('.oxyplug-preload-invalid').length === 0) {
      $.ajax({
        url: ajaxurl,
        type: 'POST',
        dataType: 'json',
        data: data,
        success(response) {
          oxyplug_preload_snackbar('success', response.data.messages[0]);
        },
        error(response) {
          console.log('response', response);
          oxyplug_preload_snackbar('error', response.responseJSON.messages[0], 0);
        },
        complete() {
          $this.trigger('oxypreloadload');
        }
      });
    } else {
      $this.trigger('oxypreloadload');
    }
  });

  const $has_loading_el = $('.oxyplug-preload-has-loading');
  $has_loading_el.on('click', function () {
    const $this = $(this);
    $this.next('.oxyplug-preload-spinner-wrap').addClass('show');
    setTimeout(function () {
      $this.prop('disabled', true);
    }, 100);
  });

  $has_loading_el.on('oxypreloadload', function () {
    const $this = $(this);
    $this.next('.oxyplug-preload-spinner-wrap').removeClass('show');
    setTimeout(function () {
      $this.prop('disabled', false);
    }, 200);
  });

  function oxyplug_preload_snackbar(status, message, timeout_in_milliseconds, anchor, title) {
    const $ = jQuery
    if (message) {
      if (status >= 200 && status <= 299) {
        status = 'success'
      } else if (status >= 400 && status <= 599) {
        status = 'error'
      }

      // 500 milliseconds per word for timeout
      if (timeout_in_milliseconds === undefined) {
        timeout_in_milliseconds = message.split(/\s+/).length * 500
      }
      if (timeout_in_milliseconds != 0 && timeout_in_milliseconds < 4500) {
        timeout_in_milliseconds = 4500
      }

      if (!title) {
        if (status == 'success') {
          title = 'Success!'
        } else if (status == 'error') {
          title = 'Failure!'
        } else if (status == 'warning') {
          title = 'Warning!'
        } else if (status == 'info') {
          title = 'Info'
        }
      }

      let $snackbar = $('#oxyplug-preload-snackbar')
      if ($snackbar.length) {
        $snackbar.find('.oxyplug-preload-close').trigger('click')
        $snackbar[0].className = `oxyplug-preload-${status}`

        const $second_part = $snackbar.find('.second-part')
        $second_part.find('h3').text(title)
        $second_part.find('p').text(message)
        if (anchor) {
          $second_part.find('a').attr('href', anchor.href).text(anchor.text).show()
        } else {
          $second_part.find('a').hide()
        }
      } else {
        $snackbar = $('<div>', {id: 'oxyplug-preload-snackbar', class: `oxyplug-preload-${status}`})
        const $first_part = $('<div>', {class: 'first-part'})
        const $first_part_icon = $('<span>')
        $first_part.append($first_part_icon)

        const $second_part = $('<div>', {class: 'second-part'})
        const $second_part_title = $('<h3>').text(title)
        const $second_part_message = $('<p>').text(message)
        $second_part.append($second_part_title, $second_part_message)
        if (anchor) {
          const $second_part_anchor = $('<a>')
          $second_part_anchor.attr('href', anchor.href).text(anchor.text)
          $second_part.append($second_part_title, $second_part_message, $second_part_anchor)
        }

        const $third_part = $('<div>', {class: 'third-part'})
        const $close = $('<span>', {class: 'oxyplug-preload-close'})
        $close.on('click', function () {
          $('#oxyplug-preload-snackbar').removeClass('show')
        })
        $third_part.append($close)

        $snackbar.append($first_part, $second_part, $third_part)
        $('body').append($snackbar)
      }

      setTimeout(function () {
        $snackbar.addClass('show')
        if (timeout_in_milliseconds > 0) {
          setTimeout(function () {
            $snackbar.removeClass('show')
          }, timeout_in_milliseconds)
        }
      }, 100)

    }
  }
});