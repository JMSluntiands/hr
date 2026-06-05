/**
 * Philippine Standard Time (Asia/Manila) helpers for browser-side dates.
 */
(function (global) {
  var TZ = "Asia/Manila";

  function getManilaTodayYmd() {
    return new Intl.DateTimeFormat("en-CA", {
      timeZone: TZ,
      year: "numeric",
      month: "2-digit",
      day: "2-digit",
    }).format(new Date());
  }

  function formatManilaDate(value, options) {
    if (!value) return "—";
    var d = value instanceof Date ? value : new Date(value);
    if (isNaN(d.getTime())) return "—";
    var opts = Object.assign(
      {
        timeZone: TZ,
        month: "short",
        day: "numeric",
        year: "numeric",
      },
      options || {}
    );
    return d.toLocaleDateString("en-PH", opts);
  }

  function formatManilaDateTime(value, options) {
    if (!value) return "—";
    var d = value instanceof Date ? value : new Date(value);
    if (isNaN(d.getTime())) return "—";
    var opts = Object.assign(
      {
        timeZone: TZ,
        month: "short",
        day: "numeric",
        year: "numeric",
        hour: "numeric",
        minute: "2-digit",
        second: "2-digit",
        hour12: true,
      },
      options || {}
    );
    return d.toLocaleString("en-PH", opts);
  }

  global.HrManilaTime = {
    TZ: TZ,
    getTodayYmd: getManilaTodayYmd,
    formatDate: formatManilaDate,
    formatDateTime: formatManilaDateTime,
  };
})(typeof window !== "undefined" ? window : this);
