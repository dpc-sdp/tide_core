import { getJson, initApp, setJson } from '@dpc-sdp/tide-content-collection-ui'
import '@dpc-sdp/tide-content-collection-ui/styles'

  ;(($) => {
  Drupal.behaviors.contentCollection = {
    attach: function (context) {
      once('content-collection-init', '.content-collection-app', context).forEach(function (container) {
        const wrap = container.closest('.field--type-content-collection')
        const index = container.getAttribute('data-index') || '0'
        const config = container.getAttribute('data-config') || '{}'
        const field = wrap?.querySelector(`#content-collection-value-${index}`)

        if (field) {
          initApp(container, {
            index,
            form: getJson(field.value),
            config: getJson(config),
            update: (form) => (field.value = setJson(form)),
            baseUrl: import.meta.env.VITE_API_URL || window.location.origin
          })
        }
      })
    }
  }
})(jQuery)
