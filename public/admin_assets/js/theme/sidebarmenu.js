// var at = document.documentElement.getAttribute("data-layout");

// // ------------------------------------------------------------------
// // Breadcrumb "Back" reliability:
// // On sidebar navigation click, store the current page URL into the
// // same sessionStorage stack used by breadcrum.blade.php.
// // ------------------------------------------------------------------
// (function initSidebarBackStack() {
//   var NAV_STACK_KEY = "sargam_breadcrumb_back_stack_v1";
//   function isSameOrigin(url) {
//     try {
//       return new URL(url).origin === window.location.origin;
//     } catch (e) {
//       return false;
//     }
//   }

//   function safeParse(json, fallback) {
//     try {
//       var val = JSON.parse(json);
//       return val == null ? fallback : val;
//     } catch (e) {
//       return fallback;
//     }
//   }

//   function getStack() {
//     var raw = sessionStorage.getItem(NAV_STACK_KEY);
//     return safeParse(raw, []);
//   }

//   function setStack(stack) {
//     // Avoid unbounded growth.
//     var trimmed = Array.isArray(stack) ? stack.slice(-20) : [];
//     sessionStorage.setItem(NAV_STACK_KEY, JSON.stringify(trimmed));
//   }

//   function pushUrl(url) {
//     if (!url || !isSameOrigin(url)) return;
//     var stack = getStack();
//     if (!Array.isArray(stack)) stack = [];
//     // Dedup while preserving order.
//     var deduped = stack.filter(function (u) {
//       return u !== url;
//     });
//     deduped.push(url);
//     setStack(deduped);
//   }

//   document.addEventListener(
//     "DOMContentLoaded",
//     function () {
//       document.addEventListener(
//         "click",
//         function (e) {
//           var a = e.target && e.target.closest ? e.target.closest("a") : null;
//           if (!a) return;

//           var inVertical = a.closest && a.closest("#sidebarnav");
//           var inHorizontal = a.closest && a.closest("#sidebarnavh");
//           if (!inVertical && !inHorizontal) return;

//           // Ignore external opens.
//           if (a.getAttribute("target") && a.getAttribute("target").toLowerCase() === "_blank") {
//             return;
//           }

//           var href = a.getAttribute("href") || "";
//           if (!href || href === "#" || href.indexOf("javascript:") === 0) return;

//           // Skip collapse toggles like href="#generalCollapse"
//           if (href.charAt(0) === "#" && a.getAttribute("data-bs-toggle") === "collapse") return;
//           if (href.charAt(0) === "#" && (a.getAttribute("role") === "button" || a.classList && a.classList.contains("has-arrow"))) {
//             return;
//           }

//           // If navigation will happen, store the current page as "previous".
//           // (New page load will push the new URL on top via breadcrumb component.)
//           pushUrl(window.location.href);
//         },
//         false
//       );
//     },
//     false
//   );
// })();

// // ------------------------------------------------------------------
// // Sidebar expand/collapse persistence (per-URL) for reliable "Back".
// // Stores current sidebar UI state before navigation and restores it on
// // load for the target URL.
// // ------------------------------------------------------------------
// (function initSidebarStatePersistence() {
//   var STATE_STACK_KEY = "sargam_sidebar_state_stack_v1";

//   function safeParse(json, fallback) {
//     try {
//       var val = JSON.parse(json);
//       return val == null ? fallback : val;
//     } catch (e) {
//       return fallback;
//     }
//   }

//   function getStateStack() {
//     var raw = sessionStorage.getItem(STATE_STACK_KEY);
//     return safeParse(raw, []);
//   }

//   function setStateStack(stack) {
//     var trimmed = Array.isArray(stack) ? stack.slice(-20) : [];
//     sessionStorage.setItem(STATE_STACK_KEY, JSON.stringify(trimmed));
//   }

//   function getCurrentState() {
//     var sidebarType = document.body ? document.body.getAttribute("data-sidebartype") : null;
//     var mainWrapper = document.getElementById("main-wrapper");
//     var showSidebar = !!(mainWrapper && mainWrapper.classList.contains("show-sidebar"));

//     // Theme toggles "close" on .sidebarmenu when sidebar is collapsed.
//     var hasCloseClass = false;
//     document.querySelectorAll(".sidebarmenu").forEach(function (el) {
//       if (el.classList.contains("close")) hasCloseClass = true;
//     });

//     return {
//       sidebarType: sidebarType,
//       showSidebar: showSidebar,
//       closed: hasCloseClass,
//     };
//   }

//   function applyState(state) {
//     if (!state || !document.body) return;

//     if (state.sidebarType) {
//       document.body.setAttribute("data-sidebartype", state.sidebarType);
//       var fullSidebarElement = document.getElementById("full-sidebar");
//       var miniSidebarElement = document.getElementById("mini-sidebar");
//       if (fullSidebarElement) fullSidebarElement.checked = state.sidebarType === "full";
//       if (miniSidebarElement) miniSidebarElement.checked = state.sidebarType === "mini-sidebar";
//     }

//     var mainWrapper = document.getElementById("main-wrapper");
//     if (mainWrapper) {
//       if (state.showSidebar) mainWrapper.classList.add("show-sidebar");
//       else mainWrapper.classList.remove("show-sidebar");
//     }

//     document.querySelectorAll(".sidebarmenu").forEach(function (el) {
//       if (state.closed) el.classList.add("close");
//       else el.classList.remove("close");
//     });
//   }

//   function saveForCurrentUrl() {
//     try {
//       var currentUrl = window.location.href;
//       var stack = getStateStack();

//       stack = stack.filter(function (item) {
//         return item && item.url !== currentUrl;
//       });
//       stack.push({ url: currentUrl, state: getCurrentState() });
//       setStateStack(stack);
//     } catch (e) {
//       // ignore
//     }
//   }

//   function restoreForCurrentUrl() {
//     try {
//       var currentUrl = window.location.href;
//       var stack = getStateStack();
//       var found = null;

//       // Take the last matching entry if duplicates exist.
//       for (var i = 0; i < stack.length; i++) {
//         if (stack[i] && stack[i].url === currentUrl) {
//           found = stack[i].state;
//         }
//       }

//       if (found) applyState(found);
//     } catch (e) {
//       // ignore
//     }
//   }

//   document.addEventListener("DOMContentLoaded", function () {
//     // Run after other DOMContentLoaded handlers (theme may set sidebarType too).
//     setTimeout(function () {
//       restoreForCurrentUrl();
//     }, 0);
//   });

//   // Store right before leaving the page (works with redirects like our Back).
//   window.addEventListener("beforeunload", function () {
//     saveForCurrentUrl();
//   });
// })();

// if ((at = "vertical")) {
//   // ==============================================================
//   // Auto select left navbar
//   // ==============================================================

//   document.addEventListener("DOMContentLoaded", function () {
//     "use strict";
//     var isSidebar = document.getElementsByClassName("side-mini-panel");
//     if (isSidebar.length > 0) {
//       var url = window.location + "";
//       var path = url.replace(
//         window.location.protocol + "//" + window.location.host + "/",
//         ""
//       );

//       //****************************
//       // This is for
//       //****************************

//       function findMatchingElement() {
//         var currentUrl = window.location.href;
//         var anchors = document.querySelectorAll("#sidebarnav a");

//         for (var i = 0; i < anchors.length; i++) {
//           if (anchors[i].href === currentUrl) {
//             return anchors[i];
//           }
//         }

//         return null; // Return null if no matching element is found
//       }

//       var elements = findMatchingElement();

//       if (elements) {
//         // Do something with the matching element
//         elements.classList.add("active");
//       }

//       //****************************
//       // This is for the multilevel menu
//       //****************************
//       document.querySelectorAll("#sidebarnav a").forEach(function (link) {
//         link.addEventListener("click", function (e) {
//           const isActive = this.classList.contains("active");
//           const parentUl = this.closest("ul");

//           if (!isActive) {
//             // hide any open menus and remove all other classes
//             parentUl.querySelectorAll("ul").forEach(function (submenu) {
//               submenu.classList.remove("in");
//             });
//             parentUl.querySelectorAll("a").forEach(function (navLink) {
//               navLink.classList.remove("active");
//             });

//             // open our new menu and add the open class
//             const submenu = this.nextElementSibling;
//             if (submenu) {
//               submenu.classList.add("in");
//             }

//             this.classList.add("active");
//           } else {
//             this.classList.remove("active");
//             parentUl.classList.remove("active");
//             const submenu = this.nextElementSibling;
//             if (submenu) {
//               submenu.classList.remove("in");
//             }
//           }
//         });
//       });

//       document
//         .querySelectorAll("#sidebarnav > li > a.has-arrow")
//         .forEach(function (link) {
//           link.addEventListener("click", function (e) {
//             e.preventDefault();
//           });
//         });

//       //****************************
//       // This is for show menu
//       //****************************

//       if (elements) {
//         var closestNav = elements.closest("nav[class^=sidebar-nav]");
//         var menuid = (closestNav && closestNav.id) || "menu-right-mini-1";
//         var menu = menuid[menuid.length - 1];

//         var menuElement = document.getElementById("menu-right-mini-" + menu);
//         var miniElement = document.getElementById("mini-" + menu);
        
//         if (menuElement) {
//           menuElement.classList.add("d-block");
//         }
//         if (miniElement) {
//           miniElement.classList.add("selected");
//         }
//       }

//       //****************************
//       // This is for mini sidebar
//       //****************************
//       document
//         .querySelectorAll("ul#sidebarnav ul li a.active")
//         .forEach(function (link) {
//           link.closest("ul").classList.add("in");
//           link.closest("ul").parentElement.classList.add("selected");
//         });
//       // Mini-nav click handling is now done globally by sidebar-navigation-fixed.js
//       // to prevent duplicate event listeners that cause multi-click issues
//     }
//   });
// }

// if ((at = "horizontal")) {
//   function findMatchingElement() {
//     var currentUrl = window.location.href;
//     var anchors = document.querySelectorAll("#sidebarnavh ul#sidebarnav a");
//     for (var i = 0; i < anchors.length; i++) {
//       if (anchors[i].href === currentUrl) {
//         return anchors[i];
//       }
//     }

// //     return null; // Return null if no matching element is found
// //   }
// //   var elements = findMatchingElement();

// //   if (elements) {
// //     elements.classList.add("active");
// //   }
// //   document
// //     .querySelectorAll("#sidebarnavh ul#sidebarnav a.active")
// //     .forEach(function (link) {
// //       link.closest("a").parentElement.classList.add("selected");
// //       link.closest("ul").parentElement.classList.add("selected");
// //     });
// // }

// // // ----------------------------------------
// // // Active 2 file at same time 
// // // ----------------------------------------

// // var currentURL =
// //   window.location != window.parent.location
// //     ? document.referrer
// //     : document.location.href;

// // var link = document.getElementById("get-url");

// // if (link) {
// //   if (currentURL.includes("/main/index.html")) {
// //     link.setAttribute("href", "../main/index.html");
// //   } else if (currentURL.includes("/index.html")) {
// //     link.setAttribute("href", "./index.html");
// //   } else {
// //     link.setAttribute("href", "./");
// //   }
// // }