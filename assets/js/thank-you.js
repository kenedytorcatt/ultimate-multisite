(() => {
"use strict";
const TransitionText = (element, has_icon = false) => {
  return {
    classes: [],
    has_icon: false,
    original_value: element.innerHTML,
    get_icon() {
      return this.has_icon ? '<span class="wu-spin wu-inline-block wu-mr-2"><span class="dashicons-wu-loader"></span></span>' : "";
    },
    clear_classes() {
      element.classList.remove(...this.classes);
    },
    add_classes(classes) {
      this.classes = classes;
      element.classList.add(...classes);
    },
    text(text, classes, toggle_icon = false) {
      this.clear_classes();
      if (toggle_icon) {
        this.has_icon = !this.has_icon;
      }
      element.animate([
        {
          opacity: "1"
        },
        {
          opacity: "0.75"
        }
      ], {
        duration: 300,
        iterations: 1
      });
      setTimeout(() => {
        this.add_classes(classes ?? []);
        element.innerHTML = this.get_icon() + text;
        element.style.opacity = "0.75";
      }, 300);
      return this;
    },
    done(timeout = 5e3) {
      setTimeout(() => {
        element.animate([
          {
            opacity: "0.75"
          },
          {
            opacity: "1"
          }
        ], {
          duration: 300,
          iterations: 1
        });
        setTimeout(() => {
          this.clear_classes();
          element.innerHTML = this.original_value;
          element.style.opacity = "1";
        }, 300);
      }, timeout);
      return this;
    }
  };
};
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".wu-resend-verification-email").forEach((element) => element.addEventListener("click", async (event) => {
    event.preventDefault();
    const transitional_text = TransitionText(element, true).text(wu_thank_you.i18n.resending_verification_email, ["wu-text-gray-400"]);
    const request = await fetch(
      wu_thank_you.ajaxurl,
      {
        method: "POST",
        headers: {
         "Content-Type": "application/x-www-form-urlencoded",
        },
        body: new URLSearchParams({
            action: "wu_resend_verification_email",
            _ajax_nonce: wu_thank_you.resend_verification_email_nonce
        }),
      }
    );
    const response = await request.json();
    if (response.success) {
      transitional_text.text(wu_thank_you.i18n.email_sent, ["wu-text-green-700"], true).done();
    } else {
      transitional_text.text(response.data[0].message, ["wu-text-red-600"], true).done();
    }
  }));
  if (!document.getElementById("wu-sites")) {
    return;
  }
  const { Vue, defineComponent } = window.wu_vue;
  window.wu_sites = new Vue(defineComponent({
    el: "#wu-sites",
    data() {
      return {
        creating: wu_thank_you.creating,
        next_queue: parseInt(wu_thank_you.next_queue, 10) + 5,
        random: 0,
        progress_in_seconds: 0,
        stopped_count: 0
      };
    },
    computed: {
      progress() {
        return Math.round(this.progress_in_seconds / this.next_queue * 100);
      }
    },
    mounted() {
      /*
       * Always start polling for site creation, regardless of
       * has_pending_site state at page load.
       *
       * With async payment gateways (Stripe webhooks), the thank-you
       * page loads before on_order_completed fires, so has_pending_site
       * is false at mount time. Without this fix, users were stuck on
       * "Creating your site" for the full countdown timer (4+ minutes)
       * even though the site was ready in ~30 seconds.
       *
       * The AJAX endpoint handles all states gracefully:
       * - No pending site yet: returns non-completed status, polling continues
       * - Pending site exists, still creating: returns "running"
       * - Site ready: returns "completed", triggers reload
       *
       * @since 2.4.13
       */
      this.check_site_created();
    },
    methods: {
      async check_site_created() {
        const url = new URL(wu_thank_you.ajaxurl);
        url.searchParams.set("action", "wu_check_pending_site_created");
        url.searchParams.set("membership_hash", wu_thank_you.membership_hash);

        /*
         * Track polling state for adaptive polling and cron kicks.
         */
        if (typeof this.running_count === "undefined") {
          this.running_count = 0;
          this.stopped_count = 0;
        }

        let response;

        try {
          response = await fetch(url).then((request) => request.json());
        } catch (e) {
          /*
           * Network error or non-JSON response (e.g. Cloudflare challenge).
           * Retry in 3s without stopping the polling loop.
           */
          this.stopped_count++;
          setTimeout(this.check_site_created, 3000);
          return;
        }

        if (response.publish_status === "completed") {
          window.location.reload();
        } else {
          this.creating = response.publish_status === "running";
          this.running_count++;

          /*
           * Kick wp-cron.php every 3 polls to keep Action Scheduler active.
           */
          if (this.running_count % 3 === 0) {
            fetch("/wp-cron.php?doing_wp_cron").catch(() => {});
          }

          /*
           * Adaptive polling: 1.5s for the first 30s, then 3s.
           * Force-reload at 60 polls (~90s) as a safety net.
           */
          if (this.running_count >= 60) {
            window.location.reload();
            return;
          }

          const delay = this.running_count < 20 ? 1500 : 3000;
          setTimeout(this.check_site_created, delay);
        }
      }
    }
  }));
});
})()