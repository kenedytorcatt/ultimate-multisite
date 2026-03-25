/* global wu_on_load, wp */
(function($) {

  // eslint-disable-next-line no-undef
  wu = {
    tables: {},
    configs: {},
  };

  $(document).ready(function() {

    wu_on_load();

    /**
     * Primary domain toggle: show a confirmation dialog before enabling.
     *
     * Uses event delegation so it works for both the edit-domain page and
     * the "Add Domain" modal which is injected into the DOM after page load.
     */
    $(document).on('change', '[data-wu-primary-domain-toggle]', function() {
      var $toggle = $(this);

      // Only prompt when the user is turning the toggle ON.
      if ( ! $toggle.is(':checked')) {
        return;
      }

      // eslint-disable-next-line no-alert
      var confirmed = window.confirm(
        // translators: Confirmation dialog shown before setting a domain as the main WaaS domain.
        wp.i18n.__(
          'Are you sure you want to change the main domain used for this WP Multisite WaaS website?\n\nThis will affect all URLs across your entire network and may cause a temporary disruption. Ensure your DNS is correctly configured before proceeding.',
          'ultimate-multisite'
        )
      );

      if ( ! confirmed) {
        // Revert the checkbox state and trigger 'change' so Vue v-model stays in sync.
        $toggle.prop('checked', false).trigger('change');
      }
    });

  });

}(jQuery));
