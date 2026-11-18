/* ========================================================= tooltips js ========================================================= */
(function (ux4g) {
  if (!ux4g) return;
  const U = ux4g.U;

  function repositionManualTooltip(tooltip) {
    if (!tooltip || tooltip.dataset.uxAdjusted === "true") return;

    const wrapper = tooltip.closest('.ux4g-tooltip-wrapper');
    if (!wrapper) return;

    // We need to know where it *will* be once it's fully hovered
    const vw = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0);
    const vh = Math.max(document.documentElement.clientHeight || 0, window.innerHeight || 0);

    const originalTransition = tooltip.style.transition;
    const originalDisplay = tooltip.style.display;
    const originalOpacity = tooltip.style.opacity;

    // Temporarily disable transition and force show to measure target state
    tooltip.style.transition = 'none';
    tooltip.style.display = 'flex';
    tooltip.style.opacity = '0';

    // Force reflow
    tooltip.offsetHeight;

    let rect = tooltip.getBoundingClientRect();
    const wrapperRect = wrapper.getBoundingClientRect();
    const padding = 18;

    // Save original class to restore on mouseout
    if (!tooltip.dataset.uxOriginalClass) {
      tooltip.dataset.uxOriginalClass = tooltip.className;
    }

    let className = tooltip.className;
    let changed = false;

    // Vertical flip
    if (className.includes('-top-') && rect.top < padding) {
      if (wrapperRect.bottom + rect.height + padding <= vh) {
        className = className.replace('-top-', '-bottom-');
        changed = true;
      }
    } else if (className.includes('-bottom-') && rect.bottom > vh - padding) {
      if (wrapperRect.top - rect.height - padding >= 0) {
        className = className.replace('-bottom-', '-top-');
        changed = true;
      }
    }

    // Horizontal flip
    if (className.includes('-left-') && !className.includes('-top-left') && !className.includes('-bottom-left') && rect.left < padding) {
      if (wrapperRect.right + rect.width + padding <= vw) {
        className = className.replace('-left-', '-right-');
        changed = true;
      }
    } else if (className.includes('-right-') && !className.includes('-top-right') && !className.includes('-bottom-right') && rect.right > vw - padding) {
      if (wrapperRect.left - rect.width - padding >= 0) {
        className = className.replace('-right-', '-left-');
        changed = true;
      }
    }

    if (changed) {
      tooltip.className = className;
      tooltip.dataset.uxAdjusted = "true";
      rect = tooltip.getBoundingClientRect(); // Re-measure after class change
    }

    let shiftX = 0;
    let shiftY = 0;

    // Horizontal adjustment for top/bottom tooltips if still out of bounds
    if (className.includes('-top-') || className.includes('-bottom-')) {
        if (rect.left < padding) {
          shiftX = padding - rect.left;
        } else if (rect.right > vw - padding) {
          shiftX = (vw - padding) - rect.right;
        }
    }

    // Vertical adjustment for left/right tooltips if still out of bounds
    if (className.includes('-left-') || className.includes('-right-')) {
        if (!className.includes('-top-') && !className.includes('-bottom-')) {
            if (rect.top < padding) {
              shiftY = padding - rect.top;
            } else if (rect.bottom > vh - padding) {
              shiftY = (vh - padding) - rect.bottom;
            }
        }
    }

    if (shiftX !== 0 || shiftY !== 0) {
      const computedStyle = window.getComputedStyle(tooltip);
      const currentTransform = computedStyle.transform === 'none' ? '' : computedStyle.transform;
      // Apply the fix
      tooltip.style.transform = `${currentTransform} translate(${shiftX}px, ${shiftY}px)`;
      tooltip.dataset.uxAdjusted = "true";
    }

    // Restore state (but keep the transform)
    tooltip.style.display = originalDisplay;
    tooltip.style.opacity = originalOpacity;
    // Delay restoring transition slightly to avoid "flying" into place from old position
    setTimeout(() => {
      tooltip.style.transition = originalTransition;
    }, 50);
  }

  // Initialize
  window.ux4gCustomInitList = window.ux4gCustomInitList || []; window.ux4gCustomInitList.push(() => {
    // Data API init
    if (ux4g.Tooltip && ux4g.Tooltip.getOrCreate) {
      U.qsa('[data-bs-toggle="tooltip"],[data-ux-toggle="tooltip"]').forEach(el => ux4g.Tooltip.getOrCreate(el));
    }

    // Auto-fix manual tooltips on hover
    document.body.addEventListener('mouseover', (e) => {
      const wrapper = e.target.closest('.ux4g-tooltip-wrapper');
      if (wrapper) {
        const tooltip = wrapper.querySelector('.ux4g-tooltip');
        if (tooltip) repositionManualTooltip(tooltip);
      }
    });

    document.body.addEventListener('mouseout', (e) => {
      const wrapper = e.target.closest('.ux4g-tooltip-wrapper');
      if (wrapper && !wrapper.contains(e.relatedTarget)) {
        const tooltip = wrapper.querySelector('.ux4g-tooltip');
        if (tooltip) {
          tooltip.style.transform = '';
          if (tooltip.dataset.uxOriginalClass) {
            tooltip.className = tooltip.dataset.uxOriginalClass;
            delete tooltip.dataset.uxOriginalClass;
          }
          delete tooltip.dataset.uxAdjusted;
        }
      }
    });
  });

  /**
   * Helper to ensure menus stay within viewport and break out of clipping bounds
   */
  ux4g.repositionMenu = function (container, menu) {
    if (!menu) return;

    // Temporarily reset to absolute to calculate natural layout sizes
    menu.style.position = "absolute";
    menu.style.width = ""; 
    menu.style.top = "";
    menu.style.bottom = "";
    menu.style.left = "";
    menu.style.right = "";
    menu.style.margin = "0";

    // Measure natural absolute width BEFORE fixing position
    const absoluteWidth = menu.offsetWidth;

    // Switch to fixed to break out of overflow boundaries
    menu.style.position = "fixed";
    menu.style.width = absoluteWidth + "px"; // lock width

    const vh = window.innerHeight;
    const vw = window.innerWidth;
    const containerRect = container.getBoundingClientRect();
    
    let offsetStr = getComputedStyle(menu).getPropertyValue('--ux4g-dropdown-menu-offset-y') ||
        getComputedStyle(menu).getPropertyValue('--ux4g-combobox-menu-offset-y') || '6px';
    let offset = parseInt(offsetStr, 10) || 6;

    // A fixed descendant is positioned against a transformed ancestor rather
    // than the viewport. Keep nested menus local to floating overlays so the
    // viewport coordinates below are not applied a second time.
    const floatingAncestor = container.closest('.ux4g-popover, .ux4g-tooltip');
    if (floatingAncestor) {
      menu.style.position = "absolute";
      menu.style.top = (containerRect.height + offset) + "px";
      menu.style.bottom = "auto";
      menu.style.left = "0";
      menu.style.right = "auto";

      let menuRect = menu.getBoundingClientRect();
      if (menuRect.bottom > vh && containerRect.top > menuRect.height + offset) {
        menu.style.top = "auto";
        menu.style.bottom = (containerRect.height + offset) + "px";
      }

      menuRect = menu.getBoundingClientRect();
      if (menuRect.right > vw) {
        menu.style.left = "auto";
        menu.style.right = "0";
      }
      return;
    }

    let top = containerRect.bottom + offset;
    let left = containerRect.left;

    menu.style.top = top + "px";
    menu.style.left = left + "px";

    const menuRect = menu.getBoundingClientRect();

    // Vertical Flip
    if (menuRect.bottom > vh && containerRect.top > menuRect.height) {
      menu.style.top = "auto";
      menu.style.bottom = (vh - containerRect.top + offset) + "px";
    } else {
      menu.style.top = top + "px";
      menu.style.bottom = "auto";
    }

    // Horizontal Shift
    const updatedRect = menu.getBoundingClientRect();
    if (updatedRect.right > vw) {
      menu.style.left = "auto";
      menu.style.right = (vw - containerRect.right) + "px";
    } else {
      menu.style.left = left + "px";
      menu.style.right = "auto";
    }
  };

  // Update open menus on resize/scroll
  const repositionAllMenus = () => {
    document.querySelectorAll('.ux4g-dropdown.is-open, .ux4g-combobox.is-open, .ux4g-breadcrumb-dropdown .show + .show').forEach(el => {
      let container = el;
      let menu = el.querySelector('.ux4g-dropdown-menu, .ux4g-combobox-menu, .ux4g-breadcrumb-menu');
      if (el.classList.contains('ux4g-breadcrumb-toggle')) {
        container = el.parentElement;
        menu = container.querySelector('.ux4g-breadcrumb-menu');
      }
      if (container && menu) ux4g.repositionMenu(container, menu);
    });
  };

  let rafMenu = null;
  const throttledRepositionAllMenus = () => {
    if (rafMenu) return;
    rafMenu = requestAnimationFrame(() => {
      repositionAllMenus();
      rafMenu = null;
    });
  };

  window.addEventListener('resize', throttledRepositionAllMenus);
  window.addEventListener('scroll', throttledRepositionAllMenus, { capture: true, passive: true });

  /**
   * Common Filter Core Logic
   */
  ux4g.filterCore = {
    /**
     * @param {string} text - The item text to check
     * @param {string} query - The search query
     * @param {string} mode - 'contains', 'starts-with', or 'starts-with-term'
     */
    matches: function (text, query, mode) {
      if (!query) return true;
      const t = String(text).toLowerCase();
      const q = String(query).toLowerCase().trim();

      switch (mode) {
        case "starts-with":
          return t.startsWith(q);
        case "starts-with-term": {
          // Split query into terms; ALL terms must match the start of some word in the text
          const qTerms = q.split(/\s+/).filter(Boolean);
          if (qTerms.length === 0) return true;
          const tWords = t.split(/\s+/);
          return qTerms.every((qTerm) => tWords.some((w) => w.startsWith(qTerm)));
        }
        case "contains":
        default:
          return t.includes(q);
      }
    },

    /**
     * @param {string} originalText - The text to highlight
     * @param {string} query - The search query
     * @param {string} mode - 'contains', 'starts-with', or 'starts-with-term'
     */
    highlight: function (originalText, query, mode) {
      if (!originalText) return "";
      const text = String(originalText);
      const escapeHtml = (str) =>
        String(str ?? "").replace(/[&<>"']/g, (c) => ({
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          '"': "&quot;",
          "'": "&#39;"
        }[c]));

      if (!query || !query.trim()) return escapeHtml(text);
      const q = query.trim();
      const escaped = q.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");

      let regex;
      switch (mode) {
        case "starts-with":
          regex = new RegExp(`^(${escaped})`, "i");
          break;
        case "starts-with-term": {
          // Highlight each query term individually at word boundaries
          const qTerms = q.split(/\s+/).filter(Boolean);
          if (qTerms.length === 0) return escapeHtml(text);
          const escapedTerms = qTerms.map((t) => t.replace(/[.*+?^${}()|[\]\\]/g, "\\$&"));
          regex = new RegExp(`\\b(${escapedTerms.join("|")})`, "gi");
          break;
        }
        case "contains":
        default:
          regex = new RegExp(`(${escaped})`, "i");
          break;
      }

      const parts = text.split(regex);
      let html = "";
      for (let i = 0; i < parts.length; i++) {
        if (i % 2 === 1) {
          html += `<strong>${escapeHtml(parts[i])}</strong>`;
        } else {
          html += escapeHtml(parts[i]);
        }
      }
      return html;
    }
  };

})(window.ux4g);

/* ========================================================= breadcrumb js ========================================================= */
window.ux4gCustomInitList = window.ux4gCustomInitList || []; window.ux4gCustomInitList.push(() => {
  const dropdowns = document.querySelectorAll(".ux4g-breadcrumb-dropdown");
  if (!dropdowns.length) return;

  const closeDropdown = (dropdown) => {
    const toggle = dropdown.querySelector(".ux4g-breadcrumb-toggle");
    const menu = dropdown.querySelector(".ux4g-breadcrumb-menu");

    if (!toggle || !menu) return;

    toggle.classList.remove("show");
    menu.classList.remove("show");
    toggle.setAttribute("aria-expanded", "false");
  };

  const openDropdown = (dropdown) => {
    const toggle = dropdown.querySelector(".ux4g-breadcrumb-toggle");
    const menu = dropdown.querySelector(".ux4g-breadcrumb-menu");

    if (!toggle || !menu) return;

    toggle.classList.add("show");
    menu.classList.add("show");
    toggle.setAttribute("aria-expanded", "true");
    if (window.ux4g && window.ux4g.repositionMenu) {
      window.ux4g.repositionMenu(dropdown, menu);
    }
  };

  dropdowns.forEach((dropdown) => {
    const toggle = dropdown.querySelector(".ux4g-breadcrumb-toggle");
    const menu = dropdown.querySelector(".ux4g-breadcrumb-menu");

    if (!toggle || !menu) return;

    toggle.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();

      const isOpen = menu.classList.contains("show");

      dropdowns.forEach((d) => closeDropdown(d));

      if (!isOpen) openDropdown(dropdown);
    });

    menu.addEventListener("click", () => closeDropdown(dropdown));
  });

  document.addEventListener("click", () => {
    dropdowns.forEach((d) => closeDropdown(d));
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") dropdowns.forEach((d) => closeDropdown(d));
  });
});



/* ========================================================= list js ========================================================= */

// Draggable js

window.ux4gCustomInitList = window.ux4gCustomInitList || []; window.ux4gCustomInitList.push(() => {

  const list = document.querySelector(".ux4g-list-draggable");
  let draggedItem = null;

  // enable drag only from icon
  document.querySelectorAll(".ux4g-icon-outlined, .ux4g-icon").forEach(icon => {

    icon.addEventListener("dragstart", (e) => {
      draggedItem = icon.closest(".ux4g-list-item");
      draggedItem.classList.add("dragging");

      e.dataTransfer.effectAllowed = "move";
    });

  });

  document.querySelectorAll(".ux4g-list-item").forEach(item => {

    item.addEventListener("dragover", (e) => {
      e.preventDefault();
      const dragging = document.querySelector(".dragging");

      if (dragging === item) return;

      const rect = item.getBoundingClientRect();
      const offset = e.clientY - rect.top;

      if (offset > rect.height / 2) {
        item.after(dragging);
      } else {
        item.before(dragging);
      }
    });

    item.addEventListener("dragend", () => {
      draggedItem.classList.remove("dragging");
      draggedItem = null;
    });

  });

});

/* ========================================================= Modal js ========================================================= */

document.addEventListener('click', (e) => {
  const openBtn = e.target.closest('[data-modal-target], [data-bs-toggle="modal"], [data-ux-toggle="modal"], [ux4g-toggle="modal"], [data-toggle="modal"], .open-modal-btn, .modal-backdrop-btn-trigger, [data-target], [data-bs-target], [data-ux-target], [ux4g-target]');
  if (openBtn) {
    // Ignore non-modal toggles (e.g. accordion, collapse, tab, dropdown, offcanvas)
    const toggleType = openBtn.getAttribute('ux4g-toggle') ||
                       openBtn.getAttribute('data-toggle') ||
                       openBtn.getAttribute('data-bs-toggle') ||
                       openBtn.getAttribute('data-ux-toggle');
    if (toggleType && toggleType !== 'modal') {
      // Not a modal toggle — let respective component handlers process it
    } else {
      let targetSelector = openBtn.getAttribute('data-modal-target') ||
                           openBtn.getAttribute('data-target') ||
                           openBtn.getAttribute('data-bs-target') ||
                           openBtn.getAttribute('data-ux-target') ||
                           openBtn.getAttribute('ux4g-target') ||
                           openBtn.getAttribute('href');
      if (targetSelector && targetSelector.indexOf('#') !== 0 && targetSelector.indexOf('.') !== 0) {
        targetSelector = '#' + targetSelector;
      }
      try {
        const targetModal = targetSelector ? document.querySelector(targetSelector) : null;
        if (targetModal && (
          targetModal.classList.contains('ux4g-modal') ||
          targetModal.classList.contains('ux4g-modal-backdrop') ||
          targetModal.classList.contains('modal') ||
          targetModal.getAttribute('role') === 'dialog' ||
          targetModal.hasAttribute('aria-modal')
        )) {
          targetModal.classList.add('is-open');
          document.body.style.overflow = 'hidden';
          return;
        }
      } catch (err) {
        // Invalid selector, ignore
      }
    }
  }

  const closeBtn = e.target.closest('[data-close-modal], [data-bs-dismiss="modal"], [data-ux-dismiss="modal"], [data-dismiss="modal"], [ux4g-dismiss="modal"], .close-modal-btn, .ux4g-modal-close, .ux4g-modal-close-two');
  if (closeBtn) {
    // Traverse parent tree and remove is-open from all parent backdrop or modal wrappers
    let parent = closeBtn.parentElement;
    let closedAny = false;
    while (parent) {
      if (parent.classList && (parent.classList.contains('ux4g-modal-backdrop') || parent.classList.contains('ux4g-modal'))) {
        if (parent.classList.contains('is-open')) {
          parent.classList.remove('is-open');
          closedAny = true;
        }
      }
      parent = parent.parentElement;
    }
    // Fallback if is-open was not found on parent tree
    if (!closedAny) {
      const modal = closeBtn.closest('.ux4g-modal-backdrop, .ux4g-modal');
      if (modal) modal.classList.remove('is-open');
    }
    if (document.querySelectorAll('.ux4g-modal-backdrop.is-open, .ux4g-modal.is-open').length === 0) {
      document.body.style.overflow = '';
    }
    return;
  }

  const openModal = e.target.closest('.ux4g-modal-backdrop.is-open, .ux4g-modal.is-open');
  if (openModal && !e.target.closest('.ux4g-modal-box')) {
    const modals = document.querySelectorAll('.ux4g-modal-backdrop.is-open, .ux4g-modal.is-open');
    modals.forEach(m => m.classList.remove('is-open'));
    if (document.querySelectorAll('.ux4g-modal-backdrop.is-open, .ux4g-modal.is-open').length === 0) {
      document.body.style.overflow = '';
    }
  }
});

// Escape key to close all open modals — registered once, never duplicates
document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    const openModals = document.querySelectorAll('.ux4g-modal-backdrop.is-open, .ux4g-modal.is-open');
    openModals.forEach(modal => modal.classList.remove('is-open'));
    document.body.style.overflow = '';
  }
});

/* ========================================================= search js ========================================================= */

window.ux4gCustomInitList = window.ux4gCustomInitList || []; window.ux4gCustomInitList.push(() => {
  document.querySelectorAll(".ux4g-search-container").forEach((searchWrap) => {
    const input = searchWrap.querySelector(".ux4g-search-input");
    const clearBtn = searchWrap.querySelector(".ux4g-search-clear");
    const list = searchWrap.querySelector(".ux4g-search-list");
    const filterMode = searchWrap.getAttribute("ux4g-search-filter") || "contains";

    if (!input) return;

    const toggleSearchState = () => {
      searchWrap.classList.toggle("ux4g-has-value", input.value.trim() !== "");
      handleSearchFilter();
    };

    const handleSearchFilter = () => {
      if (!list) return;

      // Only show list if there's a value, the component is active (focused),
      // or it's forced by 'show-empty' class
      const isActive = searchWrap.classList.contains("ux4g-is-active");
      const hasValue = input.value.trim() !== "";
      const showEmpty = searchWrap.classList.contains("ux4g-search-show-empty");

      if (isActive || hasValue || showEmpty) {
        list.style.display = "block";
      } else {
        list.style.display = "none";
      }

      const value = input.value;
      const options = list.querySelectorAll(".ux4g-list-item");
      const isSearchable = input.id && input.id.trim() !== "";
      let visibleCount = 0;

      options.forEach(option => {
        // Store original text
        const labelNode = option.querySelector(".ux4g-list-item-start");
        if (labelNode) {
          labelNode.style.gap = "0"; // Remove gap that causes space between highlight and text
        }
        const originalText = option.dataset.originalText || (labelNode ? labelNode.textContent.trim() : option.textContent.trim());
        if (!option.dataset.originalText) option.dataset.originalText = originalText;

        const isMatch = isSearchable ? window.ux4g.filterCore.matches(originalText, value, filterMode) : true;
        option.style.display = isMatch ? "" : "none";

        if (labelNode) {
          const trimmedQuery = value.trim();
          if (isSearchable && trimmedQuery && isMatch) {
            labelNode.innerHTML = window.ux4g.filterCore.highlight(originalText, trimmedQuery, filterMode);
          } else {
            labelNode.textContent = originalText;
          }
        }
        if (isMatch) visibleCount++;
      });

      // Toggle No Results Message
      let noResults = list.querySelector(".ux4g-search-no-results");
      if (visibleCount === 0 && value.trim() !== "") {
        if (!noResults) {
          noResults = document.createElement("li");
          noResults.className = "ux4g-search-no-results ux4g-p-s ux4g-text-center ux4g-text-muted";
          noResults.style.listStyle = "none";
          noResults.textContent = "No results found";
          list.appendChild(noResults);
        }
        noResults.style.display = "";
      } else if (noResults) {
        noResults.style.display = "none";
      }

      // Optionally show list only when there's a value or interaction
      list.style.display = (value.trim() === "" && visibleCount === 0 && !searchWrap.classList.contains("ux4g-search-show-empty")) ? "none" : "";

      // If list is hidden, don't show no results either
      if (list.style.display === "none" && noResults) noResults.style.display = "none";
    };

    input.addEventListener("input", toggleSearchState);

    if (clearBtn) {
      clearBtn.addEventListener("click", () => {
        input.value = "";
        toggleSearchState();
        input.focus();
      });
    }

    input.addEventListener("focus", () => {
      searchWrap.classList.add("ux4g-is-active");
      handleSearchFilter();
    });

    input.addEventListener("blur", (e) => {
      // Small timeout to allow potential clicks on the list item itself
      setTimeout(() => {
        if (!searchWrap.contains(document.activeElement)) {
          searchWrap.classList.remove("ux4g-is-active");
          handleSearchFilter();
        }
      }, 200);
    });

    // Close when clicking outside
    document.addEventListener("mousedown", (e) => {
      if (!searchWrap.contains(e.target)) {
        searchWrap.classList.remove("ux4g-is-active");
        handleSearchFilter();
      }
    });

    // Initial state
    toggleSearchState();
  });
});

// clear input text
window.ux4gCustomInitList = window.ux4gCustomInitList || []; window.ux4gCustomInitList.push(() => {
  const toggle = (i) => i.closest(".ux4g-input-container")?.classList.toggle("ux4g-has-value", i.value.length > 0);

  document.addEventListener("input", (e) => e.target.tagName === "INPUT" && toggle(e.target));

  document.addEventListener("click", (e) => {
    const btn = e.target.closest('[aria-label="Clear input"], .ux4g-input-clear');
    if (btn) {
      const input = btn.closest(".ux4g-input-container")?.querySelector("input");
      if (input && !input.disabled && !input.readOnly) {
        input.value = "";
        toggle(input);
        input.dispatchEvent(new Event("input", { bubbles: true }));
        input.focus();
      }
    }
  });

  document.querySelectorAll(".ux4g-input-container input").forEach(toggle);
});

/* ========================================================= textarea counter js ========================================================= */
window.ux4gCustomInitList = window.ux4gCustomInitList || []; window.ux4gCustomInitList.push(() => {
  const updateCounter = (textarea) => {
    const wrapper = textarea.closest(".ux4g-textarea");
    if (!wrapper) return;

    const counter = wrapper.querySelector(".ux4g-textarea-counter");
    if (!counter) return;

    const maxLength = textarea.getAttribute("maxlength") || 0;
    const currentLength = textarea.value.length;

    // Update text content with standard "0 / 200" format 
    // Uses textContent to avoid XSS vectors or accidental HTML
    counter.textContent = `${currentLength} / ${maxLength}`;
  };

  // Add listener for all input events globally (event delegation)
  document.addEventListener("input", (e) => {
    if (e.target && e.target.matches && e.target.matches(".ux4g-textarea-input")) {
      updateCounter(e.target);
    }
  });

  // Initialize all textareas with counters on page load
  document.querySelectorAll(".ux4g-textarea-input").forEach((textarea) => {
    updateCounter(textarea);
  });
});



// ux4g drawer js

/* ---------------- */
/* CLOSE DRAWER FUNCTION */
/* ---------------- */

function closeDrawer() {
  const openDrawer = document.querySelector(".ux4g-drawer.ux4g-drawer-open");
  const openOverlay = document.querySelector(".ux4g-drawer-overlay.ux4g-drawer-open");

  if (!openDrawer || !openOverlay) return;

  openDrawer.classList.remove("ux4g-drawer-open");
  openOverlay.classList.remove("ux4g-drawer-open");

  document.body.classList.remove("ux4g-drawer-lock");
}

/* ---------------- */
/* DELEGATED CLICK HANDLERS */
/* ---------------- */

document.addEventListener("click", (e) => {
  // OPEN DRAWER
  const openTrigger = e.target.closest("[data-drawer]");
  if (openTrigger) {
    const drawerId = openTrigger.getAttribute("data-drawer");
    const drawer = document.getElementById(drawerId);
    
    if (drawer) {
      const overlay = drawer.closest(".ux4g-drawer-overlay");

      document.querySelectorAll(".ux4g-drawer-open")
        .forEach(el => el.classList.remove("ux4g-drawer-open"));

      if (overlay) overlay.classList.add("ux4g-drawer-open");
      drawer.classList.add("ux4g-drawer-open");

      document.body.classList.add("ux4g-drawer-lock");
    }
    return;
  }

  // CLOSE VIA BUTTONS
  const closeTrigger = e.target.closest("[data-drawer-close]");
  if (closeTrigger) {
    closeDrawer();
    return;
  }

  /* CLOSE VIA OVERLAY CLICK */
  const overlay = e.target.closest(".ux4g-drawer-overlay");
  if (overlay && !e.target.closest(".ux4g-drawer")) {
    closeDrawer();
  }
});

/* ---------------- */
/* CLOSE VIA ESC KEY */
/* ---------------- */

document.addEventListener("keydown", (e) => {
  if (e.key === "Escape") {
    closeDrawer();
  }
});


// ux4g table checkbox selection

const getTableRowCheckboxes = (table) => Array.from(
  table.querySelectorAll('tbody .ux4g-table-selection-col .ux4g-checkbox-input[type="checkbox"]')
).filter(input => !input.disabled);

const syncTableSelectionState = (table) => {
  if (!table) return;
  const rowCheckboxes = getTableRowCheckboxes(table);
  const selectAll = table.querySelector('thead .ux4g-checkbox-input[type="checkbox"]');

  rowCheckboxes.forEach(input => {
    const row = input.closest('tr');
    if (row) row.classList.toggle('ux4g-is-selected', input.checked);
  });

  if (!selectAll) return;
  const selectedCount = rowCheckboxes.filter(input => input.checked).length;
  selectAll.checked = rowCheckboxes.length > 0 && selectedCount === rowCheckboxes.length;
  selectAll.indeterminate = selectedCount > 0 && selectedCount < rowCheckboxes.length;
};

const initTableSelection = (root = document) => {
  root.querySelectorAll('.ux4g-table').forEach(syncTableSelectionState);
};

document.addEventListener('change', (event) => {
  const checkbox = event.target.closest?.('.ux4g-table .ux4g-checkbox-input[type="checkbox"]');
  if (!checkbox) return;

  const table = checkbox.closest('.ux4g-table');
  if (!table) return;

  if (checkbox.closest('thead')) {
    getTableRowCheckboxes(table).forEach(input => {
      input.checked = checkbox.checked;
    });
  }

  syncTableSelectionState(table);
  window.queueMicrotask(() => syncTableSelectionState(table));
  window.setTimeout(() => syncTableSelectionState(table), 0);
});

window.ux4gTableSelectionInit = initTableSelection;
window.ux4gCustomInitList = window.ux4gCustomInitList || [];
window.ux4gCustomInitList.push(() => initTableSelection());

/* ========================================================= dropdown js ========================================================= */

const closeDropdown = (dropdown) => {
  if (!dropdown) return;
  dropdown.classList.remove("is-open");
  const control = dropdown.querySelector(".ux4g-dropdown-control");
  if (control) control.setAttribute("aria-expanded", "false");
};

const openDropdown = (dropdown) => {
  if (!dropdown) return;
  const allOpen = document.querySelectorAll(".ux4g-dropdown.is-open");
  allOpen.forEach((item) => {
    if (item !== dropdown) closeDropdown(item);
  });
  dropdown.classList.add("is-open");
  const control = dropdown.querySelector(".ux4g-dropdown-control");
  if (control) control.setAttribute("aria-expanded", "true");

  const menu = dropdown.querySelector(".ux4g-dropdown-menu");
  if (menu && window.ux4g && window.ux4g.repositionMenu) {
    window.ux4g.repositionMenu(dropdown, menu);
  }
};

const setControlText = (dropdown, value) => {
  const hasValue = Boolean(value && String(value).trim());
  dropdown.classList.toggle("has-value", hasValue);

  const searchInput = dropdown.querySelector("[ux4g-dropdown-search]");
  if (searchInput) {
    if (dropdown.classList.contains("ux4g-dropdown-single")) {
      searchInput.value = value || "";
    } else {
      searchInput.value = "";
    }
    searchInput.dispatchEvent(new Event("input", { bubbles: true }));
    return;
  }

  const valueNode = dropdown.querySelector("[ux4g-dropdown-value], .ux4g-dropdown-text");
  if (!valueNode) return;
  const placeholder = valueNode.getAttribute("data-placeholder") || valueNode.dataset.placeholder || "Please select..";
  valueNode.textContent = hasValue ? value : placeholder;
  valueNode.classList.toggle("is-placeholder", !hasValue);
};

const getInputLabel = (input) => {
  if (!input) return "";
  if (input.dataset.label) return input.dataset.label.trim();
  const option = input.closest(".ux4g-dropdown-option, .ux4g-dropdown-single-option, .ux4g-dropdown-multi-option, .ux4g-list-item-row, .ux4g-list-select-item, [ux4g-dropdown-choice]");
  if (option?.getAttribute("ux4g-dropdown-choice")) return option.getAttribute("ux4g-dropdown-choice").trim();
  if (option?.dataset.choiceLabel) return option.dataset.choiceLabel.trim();
  if (option?.dataset.label) return option.dataset.label.trim();
  if (input.value && input.value !== "on") return input.value.trim();
  const text = option?.querySelector(".ux4g-checkbox-label, .ux4g-dropdown-option-title, .ux4g-list-item-start span, .ux4g-list-item-start");
  return text?.textContent?.trim() || "";
};

const getChoiceLabel = (choice) => {
  if (!choice) return "";
  if (choice.getAttribute("ux4g-dropdown-choice")) return choice.getAttribute("ux4g-dropdown-choice").trim();
  if (choice.dataset.choiceLabel) return choice.dataset.choiceLabel.trim();
  if (choice.dataset.label) return choice.dataset.label.trim();
  if (choice.value) return choice.value.trim();
  const text = choice.querySelector(".ux4g-list-item-start span, .ux4g-list-item-start, .ux4g-checkbox-label, .ux4g-dropdown-option-title");
  return text?.textContent?.trim() || choice.textContent?.trim() || "";
};

const applySingleSelection = (dropdown, value) => {
  const chipsNode = dropdown.querySelector("[ux4g-dropdown-chips]");
  dropdown.classList.remove("has-selection");
  setControlText(dropdown, value);
  if (!chipsNode) return;
  chipsNode.innerHTML = "";
};

const setSingleSelectedOption = (dropdown, selectedOption) => {
  const options = dropdown.querySelectorAll(".ux4g-dropdown-single-option, .ux4g-list-item-row, .ux4g-list-select-item");
  options.forEach((option) => {
    const isSelected = option === selectedOption;
    const input = option.querySelector("input");
    if (isSelected) {
      option.classList.add("is-selected", "active");
      option.setAttribute("aria-selected", "true");
      option.setAttribute("data-selected", "true");
      if (input) input.checked = true;
    } else {
      option.classList.remove("is-selected", "active");
      option.setAttribute("aria-selected", "false");
      option.removeAttribute("data-selected");
      if (input) input.checked = false;
    }
    const listItem = option.closest(".ux4g-list-item");
    if (listItem && listItem.getAttribute("role") === "option") listItem.setAttribute("aria-selected", String(isSelected));
  });
};

const clearSingleSelection = (dropdown) => {
  const options = dropdown.querySelectorAll(".ux4g-dropdown-single-option, .ux4g-list-item-row, .ux4g-list-select-item");
  options.forEach((option) => {
    option.classList.remove("is-selected", "active");
    if (option.getAttribute("role") === "option") option.setAttribute("aria-selected", "false");
    option.removeAttribute("data-selected");
    const input = option.querySelector("input");
    if (input) input.checked = false;
    const listItem = option.closest(".ux4g-list-item");
    if (listItem && listItem.getAttribute("role") === "option") listItem.setAttribute("aria-selected", "false");
  });
  applySingleSelection(dropdown, "");
};

const getMultiItemLabel = (item) => {
  if (!item) return "";
  if (item.dataset.choiceLabel) return item.dataset.choiceLabel.trim();
  if (item.dataset.label) return item.dataset.label.trim();
  if (item.getAttribute("ux4g-dropdown-choice")) return item.getAttribute("ux4g-dropdown-choice").trim();
  if (item.value) return item.value.trim();
  const text = item.querySelector(".ux4g-checkbox-label, .ux4g-dropdown-option-title, .ux4g-list-item-start span, .ux4g-list-item-start");
  return text?.textContent?.trim() || item.textContent?.trim() || "";
};

const renderMultiSelection = (dropdown) => {
  if (!dropdown) return;
  const chipsNode = dropdown.querySelector("[ux4g-dropdown-chips]");
  const checkedInputs = Array.from(
    dropdown.querySelectorAll(".ux4g-dropdown-option-input:checked, .ux4g-checkbox-input:checked")
  );
  const selectedRows = Array.from(
    dropdown.querySelectorAll(".ux4g-dropdown-menu .ux4g-list-item-row.active, .ux4g-dropdown-menu .ux4g-list-select-item.active, .ux4g-dropdown-menu .ux4g-dropdown-multi-option.active, .ux4g-dropdown-menu .ux4g-list-item-row.is-selected, .ux4g-dropdown-menu .ux4g-dropdown-multi-option.is-selected")
  ).filter((row) => !row.querySelector("input"));

  const totalSelected = checkedInputs.length + selectedRows.length;
  const hasSelection = totalSelected > 0;
  dropdown.classList.toggle("has-selection", hasSelection && Boolean(chipsNode));
  dropdown.classList.toggle("has-value", hasSelection);

  const options = Array.from(dropdown.querySelectorAll(".ux4g-dropdown-option, .ux4g-dropdown-multi-option, .ux4g-list-select-item"));
  options.forEach((option) => {
    const input = option.querySelector("input");
    if (input) {
      const isSelected = Boolean(input.checked);
      option.classList.toggle("is-selected", isSelected);
      option.classList.toggle("active", isSelected);
      if (option.getAttribute("role") === "option") option.setAttribute("aria-selected", String(isSelected));
    }
  });

  setControlText(dropdown, "");
  if (!chipsNode) return;
  chipsNode.innerHTML = "";
  if (!hasSelection) return;

  let chipSizeClass = "ux4g-input-chip-s";
  if (dropdown.classList.contains("ux4g-dropdown-s")) chipSizeClass = "ux4g-input-chip-xs";
  if (dropdown.classList.contains("ux4g-dropdown-l")) chipSizeClass = "ux4g-input-chip";

  checkedInputs.forEach((input) => {
    const label = getInputLabel(input);
    if (!label) return;

    const chip = document.createElement("span");
    chip.className = `${chipSizeClass} ux4g-dropdown-chip`;
    chip.setAttribute("role", "button");
    chip.setAttribute("tabindex", "0");
    chip.setAttribute("aria-label", `Remove ${label}`);

    const tokenIcon = document.createElement("span");
    tokenIcon.className = "ux4g-icon-outlined";
    tokenIcon.setAttribute("aria-hidden", "true");
    tokenIcon.textContent = "token";

    const labelSpan = document.createElement("span");
    labelSpan.textContent = label;

    const closeIcon = document.createElement("span");
    closeIcon.className = "ux4g-icon-outlined";
    closeIcon.setAttribute("aria-hidden", "true");
    closeIcon.textContent = "close";

    chip.append(tokenIcon, labelSpan, closeIcon);

    const clearSelection = (event) => {
      event.preventDefault();
      event.stopPropagation();
      input.checked = false;
      input.dispatchEvent(new Event("change", { bubbles: true }));
      renderMultiSelection(dropdown);
    };

    chip.addEventListener("mousedown", (e) => {
      e.preventDefault();
      e.stopPropagation();
    });
    const closeBtn = chip.querySelector(".ux4g-icon-outlined:last-child");
    if (closeBtn) closeBtn.addEventListener("click", clearSelection);
    chip.addEventListener("keydown", (e) => {
      if (e.key === "Enter" || e.key === " ") clearSelection(e);
    });
    chipsNode.appendChild(chip);
  });

  selectedRows.forEach((row) => {
    const label = getMultiItemLabel(row);
    if (!label) return;

    const chip = document.createElement("span");
    chip.className = `${chipSizeClass} ux4g-dropdown-chip`;
    chip.setAttribute("role", "button");
    chip.setAttribute("tabindex", "0");
    chip.setAttribute("aria-label", `Remove ${label}`);

    const tokenIcon = document.createElement("span");
    tokenIcon.className = "ux4g-icon-outlined";
    tokenIcon.setAttribute("aria-hidden", "true");
    tokenIcon.textContent = "token";

    const labelSpan = document.createElement("span");
    labelSpan.textContent = label;

    const closeIcon = document.createElement("span");
    closeIcon.className = "ux4g-icon-outlined";
    closeIcon.setAttribute("aria-hidden", "true");
    closeIcon.textContent = "close";

    chip.append(tokenIcon, labelSpan, closeIcon);

    const clearSelection = (event) => {
      event.preventDefault();
      event.stopPropagation();
      row.classList.remove("active", "is-selected");
      row.setAttribute("aria-selected", "false");
      renderMultiSelection(dropdown);
    };

    chip.addEventListener("mousedown", (e) => {
      e.preventDefault();
      e.stopPropagation();
    });
    const closeBtn = chip.querySelector(".ux4g-icon-outlined:last-child");
    if (closeBtn) closeBtn.addEventListener("click", clearSelection);
    chip.addEventListener("keydown", (e) => {
      if (e.key === "Enter" || e.key === " ") clearSelection(e);
    });
    chipsNode.appendChild(chip);
  });
};

const ux4gDropdownInit = (root = document) => {
  const dropdowns = Array.from(root.querySelectorAll(".ux4g-dropdown"))
    .filter((dropdown) => dropdown.dataset.ux4gDropdownInitialized !== "true");

  dropdowns.forEach((dropdown, index) => {
    const control = dropdown.querySelector(".ux4g-dropdown-control");
    const menu = dropdown.querySelector(".ux4g-dropdown-menu");
    if (!control || !menu) return;
    dropdown.dataset.ux4gDropdownInitialized = "true";

    if (!control.id) {
      control.id = `ux4g-dropdown-control-${Date.now()}-${index + 1}`;
    }

    const popupRole = menu.getAttribute("role") === "menu" ? "menu" : "listbox";
    control.setAttribute("aria-haspopup", popupRole);
    control.setAttribute("aria-expanded", "false");
    menu.setAttribute("aria-labelledby", control.id);

    const isMulti = dropdown.classList.contains("ux4g-dropdown-multi");

    if (isMulti) {
      const searchInput = dropdown.querySelector("[ux4g-dropdown-search]");
      if (searchInput) {
        const icon = searchInput.previousElementSibling;
        if (icon && icon.classList.contains("ux4g-icon-outlined") && !searchInput.closest(".ux4g-dropdown-input-wrap")) {
          const wrapper = document.createElement("span");
          wrapper.className = "ux4g-dropdown-input-wrap";
          icon.parentNode.insertBefore(wrapper, icon);
          wrapper.appendChild(icon);
          wrapper.appendChild(searchInput);
        }
      }
      renderMultiSelection(dropdown);
    } else {
      const preselectedSingle = menu.querySelector(
        ".ux4g-dropdown-single-option.is-selected, .ux4g-dropdown-single-option[aria-selected='true']"
      );
      if (preselectedSingle) {
        setSingleSelectedOption(dropdown, preselectedSingle);
        applySingleSelection(dropdown, getChoiceLabel(preselectedSingle));
      } else {
        const preselectedChoice = menu.querySelector("[ux4g-dropdown-choice][aria-selected='true']");
        if (preselectedChoice) {
          applySingleSelection(dropdown, getChoiceLabel(preselectedChoice));
        } else {
          const preselectedInput = menu.querySelector(".ux4g-dropdown-option-input:checked");
          if (preselectedInput) applySingleSelection(dropdown, getInputLabel(preselectedInput));
        }
      }
    }
  });
};

// Global Document-level Event Delegation for all Dropdowns
if (typeof document !== "undefined" && !document.__ux4gDropdownDelegated) {
  document.__ux4gDropdownDelegated = true;

  document.addEventListener("click", (event) => {
    // 1. Dropdown control button clicked
    const control = event.target.closest(".ux4g-dropdown-control");
    if (control) {
      const dropdown = control.closest(".ux4g-dropdown");
      if (!dropdown) return;

      if (control.disabled || control.getAttribute("aria-disabled") === "true") {
        event.preventDefault();
        event.stopPropagation();
        return;
      }

      if (event.target.closest("[ux4g-dropdown-search]")) {
        if (!dropdown.classList.contains("is-open")) openDropdown(dropdown);
        return;
      }

      event.preventDefault();
      event.stopPropagation();
      if (dropdown.classList.contains("is-open")) closeDropdown(dropdown);
      else openDropdown(dropdown);
      return;
    }

    // 2. Dropdown menu clicked
    const menu = event.target.closest(".ux4g-dropdown-menu");
    if (menu) {
      const dropdown = menu.closest(".ux4g-dropdown");
      if (!dropdown) return;

      const isMulti = dropdown.classList.contains("ux4g-dropdown-multi");
      const isSingle = dropdown.classList.contains("ux4g-dropdown-single");

      if (isMulti) {
        const checkboxLabel = event.target.closest("label.ux4g-checkbox");
        const checkboxInput = event.target.closest(".ux4g-checkbox-input, .ux4g-dropdown-option-input");
        if (!checkboxLabel && !checkboxInput) {
          const rowWithCheckbox = event.target.closest(".ux4g-list-item-row, .ux4g-list-item");
          const inputInside = rowWithCheckbox?.querySelector("input[type='checkbox']");
          if (inputInside) {
            event.preventDefault();
            event.stopPropagation();
            inputInside.checked = !inputInside.checked;
            inputInside.dispatchEvent(new Event("change", { bubbles: true }));
            renderMultiSelection(dropdown);
            return;
          }
        }

        const multiRow = event.target.closest(".ux4g-dropdown-multi-option, .ux4g-list-item-row, .ux4g-list-select-item, [ux4g-dropdown-choice]");
        if (multiRow && !multiRow.querySelector("input") && !event.target.closest(".ux4g-checkbox, .ux4g-radio, label")) {
          setTimeout(() => {
            renderMultiSelection(dropdown);
          }, 0);
          return;
        }

        setTimeout(() => {
          renderMultiSelection(dropdown);
        }, 0);
        return;
      }

      // Single Select Choice
      const choice = event.target.closest("[ux4g-dropdown-choice], .ux4g-dropdown-single-option, .ux4g-list-item-row, .ux4g-list-select-item");
      if (choice) {
        event.preventDefault();
        event.stopPropagation();
        const wasSelected = choice.getAttribute("data-selected") === "true";

        if (wasSelected) {
          // Deselect: remove active styling, reset background & text color, reset placeholder to "Please select.."
          clearSingleSelection(dropdown);
          closeDropdown(dropdown);
          return;
        }

        const value = getChoiceLabel(choice);
        if (value) {
          setSingleSelectedOption(dropdown, choice);
          applySingleSelection(dropdown, value);
          closeDropdown(dropdown);
        }
        return;
      }

      const isActionDropdown =
        dropdown.classList.contains("ux4g-dropdown-button") ||
        dropdown.classList.contains("ux4g-dropdown-overflow");

      const row = event.target.closest(".ux4g-list-item-row");
      if (isActionDropdown && row && !row.hasAttribute("ux4g-dropdown-choice")) {
        closeDropdown(dropdown);
      }
      return;
    }

    // 3. Outside Click -> Close open dropdowns
    const openDropdowns = document.querySelectorAll(".ux4g-dropdown.is-open");
    openDropdowns.forEach((d) => {
      if (!d.contains(event.target)) {
        closeDropdown(d);
      }
    });
  });

  document.addEventListener("change", (event) => {
    const input = event.target.closest(".ux4g-dropdown-option-input, .ux4g-checkbox-input, .ux4g-radio-input");
    if (!input) return;
    const dropdown = input.closest(".ux4g-dropdown");
    if (!dropdown) return;

    const isMulti = dropdown.classList.contains("ux4g-dropdown-multi");
    if (isMulti) {
      renderMultiSelection(dropdown);
      return;
    }

    if (input.checked) {
      const choice = input.closest(".ux4g-dropdown-single-option, .ux4g-list-item-row, .ux4g-list-select-item");
      if (choice) setSingleSelectedOption(dropdown, choice);
      applySingleSelection(dropdown, getInputLabel(input));
      closeDropdown(dropdown);
    } else {
      clearSingleSelection(dropdown);
      closeDropdown(dropdown);
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      const openDropdowns = document.querySelectorAll(".ux4g-dropdown.is-open");
      openDropdowns.forEach((dropdown) => {
        closeDropdown(dropdown);
      });
    }
  });
}

window.ux4gDropdownInit = ux4gDropdownInit;
window.ux4gCustomInitList = window.ux4gCustomInitList || [];
window.ux4gCustomInitList.push(() => ux4gDropdownInit(document));


/* =========================================
DROPDOWN SEARCH
========================================= */

window.ux4gCustomInitList = window.ux4gCustomInitList || []; window.ux4gCustomInitList.push(() => {
  document.querySelectorAll("[ux4g-dropdown-search]").forEach((input) => {

    const dropdown = input.closest(".ux4g-dropdown");
    const menu = dropdown.querySelector(".ux4g-dropdown-menu");

    input.addEventListener("focus", () => {
      dropdown.classList.add("is-open");
      if (window.ux4g && window.ux4g.repositionMenu) {
        window.ux4g.repositionMenu(dropdown, menu);
      }
    });

    input.addEventListener("input", () => {
      const value = input.value;
      const filterMode = dropdown.getAttribute("ux4g-dropdown-filter") || "contains";

      dropdown.classList.add("is-open");
      if (window.ux4g && window.ux4g.repositionMenu) {
        window.ux4g.repositionMenu(dropdown, menu);
      }

      const options = dropdown.querySelectorAll(
        ".ux4g-dropdown-single-option, .ux4g-dropdown-option"
      );

      options.forEach(option => {
        const originalText = option.dataset.originalText || option.textContent.trim();
        if (!option.dataset.originalText) option.dataset.originalText = originalText;

        const isMatch = window.ux4g.filterCore.matches(originalText, value, filterMode);
        option.style.display = isMatch ? "" : "none";

        // Optional: Add highlighting for Dropdown Search too
        const labelNode = option.querySelector(".ux4g-checkbox-label, .ux4g-dropdown-option-title, .ux4g-list-item-start");
        if (labelNode) {
          if (value && isMatch) {
            labelNode.innerHTML = window.ux4g.filterCore.highlight(originalText, value, filterMode);
          } else {
            labelNode.textContent = originalText;
          }
        }
      });
    });

    // Sync disabled state
    const control = dropdown.querySelector(".ux4g-dropdown-control");
    if (control && (control.disabled || control.getAttribute("aria-disabled") === "true")) {
      input.disabled = true;
    }
  });
});

/* ========================================================= combobox js ========================================================= */

window.ux4gCustomInitList = window.ux4gCustomInitList || []; window.ux4gCustomInitList.push(() => {
  const comboboxs = Array.from(document.querySelectorAll(".ux4g-combobox"));
  if (!comboboxs.length) return;

  const closeCombobox = (combobox) => {
    combobox.classList.remove("is-open");
    const control = combobox.querySelector(".ux4g-combobox-control");
    const input = combobox.querySelector("[ux4g-combobox-search]");
    if (control && control.tagName === "BUTTON") control.setAttribute("aria-expanded", "false");
    if (input) input.setAttribute("aria-expanded", "false");
  };

  const openCombobox = (combobox) => {
    comboboxs.forEach((item) => {
      if (item !== combobox) closeCombobox(item);
    });
    combobox.classList.add("is-open");
    const control = combobox.querySelector(".ux4g-combobox-control");
    const input = combobox.querySelector("[ux4g-combobox-search]");
    if (control && control.tagName === "BUTTON") control.setAttribute("aria-expanded", "true");
    if (input) input.setAttribute("aria-expanded", "true");

    const menu = combobox.querySelector(".ux4g-combobox-menu");
    if (menu && window.ux4g && window.ux4g.repositionMenu) {
      window.ux4g.repositionMenu(combobox, menu);
    }
  };

  const setControlText = (combobox, value) => {
    const searchInput = combobox.querySelector("[ux4g-combobox-search]");
    if (searchInput) {
      if (combobox.classList.contains("ux4g-combobox-single")) {
        searchInput.value = value || "";
      } else {
        searchInput.value = "";
      }
      return;
    }

    const valueNode = combobox.querySelector("[ux4g-combobox-value]");
    const hasValue = Boolean(value && String(value).trim());
    if (!valueNode) return;
    const placeholder = valueNode.getAttribute("data-placeholder") || "Please select..";
    valueNode.textContent = hasValue ? value : placeholder;
    valueNode.classList.toggle("is-placeholder", !hasValue);
  };

  const getInputLabel = (input) => {
    if (!input) return "";
    if (input.dataset.label) return input.dataset.label;
    if (input.value) return input.value;
    const option = input.closest(".ux4g-combobox-option");
    const text = option?.querySelector(".ux4g-checkbox-label, .ux4g-combobox-option-title");
    return text?.textContent?.trim() || "";
  };

  const getChoiceLabel = (choice) => {
    if (!choice) return "";
    return (choice.getAttribute("ux4g-combobox-choice") || choice.textContent || "").trim();
  };

  const applySingleSelection = (combobox, value) => {
    const chipsNode = combobox.querySelector("[ux4g-combobox-chips]");
    combobox.classList.remove("has-selection");
    setControlText(combobox, value);
    if (!chipsNode) return;
    chipsNode.innerHTML = "";
  };

  const setSingleSelectedOption = (combobox, selectedOption) => {
    const options = combobox.querySelectorAll(".ux4g-combobox-single-option");
    options.forEach((option) => {
      const isSelected = option === selectedOption;
      option.classList.toggle("is-selected", isSelected);
      option.classList.toggle("active", isSelected);
      if (option.getAttribute("role") === "option") option.setAttribute("aria-selected", String(isSelected));
      const listItem = option.closest(".ux4g-list-item");
      if (listItem && listItem.getAttribute("role") === "option") listItem.setAttribute("aria-selected", String(isSelected));
    });
  };

  const renderMultiSelection = (combobox) => {
    const chipsNode = combobox.querySelector("[ux4g-combobox-chips]");
    const checkedInputs = Array.from(
      combobox.querySelectorAll(".ux4g-combobox-option-input:checked")
    );
    const options = Array.from(combobox.querySelectorAll(".ux4g-combobox-option"));

    const hasSelection = checkedInputs.length > 0;
    combobox.classList.toggle("has-selection", hasSelection && Boolean(chipsNode));
    options.forEach((option) => {
      const input = option.querySelector(".ux4g-combobox-option-input");
      const isSelected = Boolean(input?.checked);
      option.classList.toggle("is-selected", isSelected);
      option.classList.toggle("active", isSelected);
      if (option.getAttribute("role") === "option") option.setAttribute("aria-selected", String(isSelected));
    });

    setControlText(combobox, "");
    if (!chipsNode) return;
    chipsNode.style.display = "contents";
    chipsNode.innerHTML = "";
    if (!chipsNode || !hasSelection) return;

    let chipSizeClass = "ux4g-input-chip-s";
    if (combobox.classList.contains("ux4g-combobox-s")) chipSizeClass = "ux4g-input-chip-xs";
    if (combobox.classList.contains("ux4g-combobox-l")) chipSizeClass = "ux4g-input-chip";

    checkedInputs.forEach((input) => {
      const label = getInputLabel(input);
      if (!label) return;

      const chip = document.createElement("span");
      chip.className = `${chipSizeClass} ux4g-combobox-chip`;
      chip.setAttribute("role", "button");
      chip.setAttribute("tabindex", "0");
      chip.setAttribute("aria-label", `Remove ${label}`);
      chip.setAttribute("data-input-id", input.id);

      const tokenIcon = document.createElement("span");
      tokenIcon.className = "ux4g-icon-outlined";
      tokenIcon.setAttribute("aria-hidden", "true");
      tokenIcon.textContent = "token";

      const labelSpan = document.createElement("span");
      labelSpan.textContent = label;

      const closeIcon = document.createElement("span");
      closeIcon.className = "ux4g-icon-outlined";
      closeIcon.setAttribute("aria-hidden", "true");
      closeIcon.textContent = "close";

      chip.append(tokenIcon, labelSpan, closeIcon);

      const closeBtn = chip.querySelector(".ux4g-icon-outlined:last-child");

      const clearSelection = (event) => {
        event.preventDefault();
        event.stopPropagation();
        input.checked = false;
        renderMultiSelection(combobox);
      };

      chip.addEventListener("mousedown", (event) => {
        event.preventDefault();
        event.stopPropagation();
      });

      if (closeBtn) {
        closeBtn.addEventListener("click", clearSelection);
      }

      chip.addEventListener("keydown", (event) => {
        if (event.key === "Enter" || event.key === " ") {
          clearSelection(event);
        }
      });

      chipsNode.appendChild(chip);
    });
  };

  const DEBOUNCE_DELAY = 150;
  const debounce = (fn, delay) => {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), delay);
    };
  };

  function toggleNoResults(menu, show) {
    let el = menu.querySelector(".ux4g-combobox-no-results");
    if (show) {
      if (!el) {
        el = document.createElement("div");
        el.className = "ux4g-combobox-no-results ux4g-p-s ux4g-text-center ux4g-text-muted";
        el.textContent = "No results found";
        menu.appendChild(el);
      }
      el.style.display = "";
    } else if (el) {
      el.style.display = "none";
    }
  }

  comboboxs.forEach((combobox, index) => {
    const control = combobox.querySelector(".ux4g-combobox-control");
    const menu = combobox.querySelector(".ux4g-combobox-menu");
    const input = combobox.querySelector("[ux4g-combobox-search]");
    const caret = combobox.querySelector(".ux4g-combobox-caret");

    if (!control || !menu) return;

    // Ensure all focusable descendants inside a <button> control are removed
    // from tab order to avoid nested-interactive accessibility violations.
    if (control.tagName === "BUTTON") {
      control.querySelectorAll("input, textarea, select, button").forEach((el) => {
        el.setAttribute("tabindex", "-1");
      });
    }

    if (!control.id) {
      control.id = `ux4g-combobox-control-${index + 1}`;
    }

    if (!menu.id) {
      menu.id = `ux4g-combobox-menu-${index + 1}`;
    }

    const isSingle = combobox.classList.contains("ux4g-combobox-single");
    const isMulti = combobox.classList.contains("ux4g-combobox-multi");
    const filterMode = combobox.getAttribute("ux4g-combobox-filter") || "contains";

    const popupRole = menu.getAttribute("role") === "menu" ? "menu" : (isMulti ? "dialog" : "listbox");
    menu.setAttribute("role", popupRole);
    if (isMulti) {
      if (!menu.getAttribute("aria-label")) menu.setAttribute("aria-label", "Multi select options");
    } else {
      menu.querySelectorAll("ul").forEach(ul => ul.setAttribute("role", "presentation"));
      menu.querySelectorAll("li").forEach(li => li.setAttribute("role", "presentation"));
      menu.querySelectorAll(".ux4g-combobox-single-option").forEach(opt => {
        opt.setAttribute("role", "option");
        if (!opt.hasAttribute("aria-selected")) opt.setAttribute("aria-selected", "false");
      });
    }

    if (control.tagName === "BUTTON") {
      control.setAttribute("aria-haspopup", popupRole);
      control.setAttribute("aria-expanded", "false");
    } else {
      control.removeAttribute("aria-haspopup");
      control.removeAttribute("aria-expanded");
    }

    if (input) {
      input.setAttribute("role", "combobox");
      input.setAttribute("aria-haspopup", popupRole);
      input.setAttribute("aria-expanded", "false");
      input.setAttribute("aria-controls", menu.id);
      input.setAttribute("aria-autocomplete", "list");

      const ctrlLabel = control.getAttribute("aria-label");
      if (ctrlLabel) {
        if (!input.getAttribute("aria-label") || input.getAttribute("aria-label") === "Combobox search input" || input.getAttribute("aria-label") === "Search options" || input.getAttribute("aria-label") === "Combobox input") {
          input.setAttribute("aria-label", ctrlLabel);
        }
        control.removeAttribute("aria-label");
      }
      if (input.getAttribute("tabindex") === "-1") {
        input.removeAttribute("tabindex");
      }
    }

    menu.setAttribute("aria-labelledby", input ? (input.id || control.id) : control.id);

    const comboboxInputs = Array.from(menu.querySelectorAll(".ux4g-combobox-option-input"));
    comboboxInputs.forEach((input, inputIndex) => {
      if (!input.id) {
        input.id = `${control.id}-option-${inputIndex + 1}`;
      }
    });

    const options = isMulti
      ? Array.from(combobox.querySelectorAll(".ux4g-combobox-option"))
      : Array.from(combobox.querySelectorAll(".ux4g-combobox-single-option"));

    // Store original text for filtering
    options.forEach((option) => {
      let labelNode = isMulti
        ? option.querySelector(".ux4g-checkbox-label")
        : option.querySelector(".ux4g-list-item-start");

      option.dataset.originalText = labelNode
        ? labelNode.textContent.trim()
        : option.textContent.trim();
    });

    const handleFilter = (customQuery = null) => {
      if (!input) return;
      const value = customQuery !== null ? customQuery : input.value;
      let visibleCount = 0;

      options.forEach((option) => {
        const originalText = option.dataset.originalText;
        const isMatch = customQuery === "" ? true : window.ux4g.filterCore.matches(originalText, value, filterMode);

        option.style.display = isMatch ? "" : "none";

        const labelNode = isMulti
          ? option.querySelector(".ux4g-checkbox-label")
          : option.querySelector(".ux4g-list-item-start");

       if (labelNode) {
          if (value && isMatch && customQuery !== "") {
            labelNode.innerHTML = `<span>${window.ux4g.filterCore.highlight(originalText, value, filterMode)}</span>`;
          } else {
            labelNode.textContent = originalText;
          }
        }
        if (isMatch) visibleCount++;
      });
      toggleNoResults(menu, visibleCount === 0 && Boolean(value && value.trim()));
    };

    if (input) {
      input.addEventListener("focus", () => openCombobox(combobox));
      input.addEventListener("input", debounce(() => {
        openCombobox(combobox);
        if (isSingle && input.value.trim() === "") {
          setSingleSelectedOption(combobox, null);
          applySingleSelection(combobox, "");
        }
        handleFilter();
      }, DEBOUNCE_DELAY));
    }

    if (caret) {
      caret.addEventListener("click", (e) => {
        e.stopPropagation();
        combobox.classList.contains("is-open") ? closeCombobox(combobox) : openCombobox(combobox);
      });
    }

    control.addEventListener("click", (event) => {
      if (control.disabled || control.getAttribute("aria-disabled") === "true") {
        event.preventDefault();
        event.stopPropagation();
        return;
      }

      if (event.target.closest("[ux4g-combobox-search]")) {
        if (!combobox.classList.contains("is-open")) openCombobox(combobox);
        return;
      }
      event.preventDefault();
      event.stopPropagation();
      if (combobox.classList.contains("is-open")) closeCombobox(combobox);
      else openCombobox(combobox);
    });

    menu.addEventListener("click", (event) => {
      const choice = event.target.closest("[ux4g-combobox-choice]");
      if (!choice) {
        // Handle Action Combobox closing
        const row = event.target.closest(".ux4g-list-item-row");
        if (row) {
          const isActionCombobox =
            combobox.classList.contains("ux4g-combobox-button") ||
            combobox.classList.contains("ux4g-combobox-overflow");
          if (isActionCombobox) {
            closeCombobox(combobox);
          }
        }
        return;
      }
      event.stopPropagation();

      const value = getChoiceLabel(choice);
      if (!value) return;

      if (isSingle) {
        const isAlreadySelected = choice.classList.contains("is-selected") || choice.classList.contains("active");
        if (isAlreadySelected) {
          setSingleSelectedOption(combobox, null);
          applySingleSelection(combobox, "");
        } else {
          setSingleSelectedOption(combobox, choice);
          applySingleSelection(combobox, value);
        }
        handleFilter(""); // Reset highlight and show all options
      } else if (!isMulti) {
        applySingleSelection(combobox, value);
      }
      closeCombobox(combobox);
    });

    menu.addEventListener("change", (event) => {
      const inputEl = event.target.closest(".ux4g-combobox-option-input");
      if (!inputEl) return;

      if (isMulti) {
        renderMultiSelection(combobox);
        handleFilter();
        return;
      }

      if (inputEl.checked && !isMulti) {
        menu.querySelectorAll(".ux4g-combobox-option-input").forEach((item) => {
          if (item !== inputEl) item.checked = false;
        });
      }

      applySingleSelection(combobox, inputEl.checked ? getInputLabel(inputEl) : "");
      closeCombobox(combobox);
    });

    if (isMulti) {
      if (input) {
        const icon = input.previousElementSibling;
        if (icon && icon.classList.contains("ux4g-icon-outlined") && !input.closest(".ux4g-combobox-input-wrap")) {
          const wrapper = document.createElement("span");
          wrapper.className = "ux4g-combobox-input-wrap";
          icon.parentNode.insertBefore(wrapper, icon);
          wrapper.appendChild(icon);
          wrapper.appendChild(input);
        }
      }
      renderMultiSelection(combobox);
      return;
    }

    const preselectedSingle = menu.querySelector(
      ".ux4g-combobox-single-option.is-selected, .ux4g-combobox-single-option[aria-selected='true']"
    );
    if (preselectedSingle) {
      setSingleSelectedOption(combobox, preselectedSingle);
      applySingleSelection(combobox, getChoiceLabel(preselectedSingle));
      return;
    }

    const preselectedChoice = menu.querySelector("[ux4g-combobox-choice][aria-selected='true']");
    if (preselectedChoice) {
      applySingleSelection(combobox, getChoiceLabel(preselectedChoice));
      return;
    }

    const preselectedInput = menu.querySelector(".ux4g-combobox-option-input:checked");
    if (preselectedInput) {
      applySingleSelection(combobox, getInputLabel(preselectedInput));
    }
  });

  document.addEventListener("click", (event) => {
    const openComboboxes = document.querySelectorAll(".ux4g-combobox.is-open");
    openComboboxes.forEach((combobox) => {
      if (!combobox.contains(event.target)) {
        combobox.classList.remove("is-open");
        const control = combobox.querySelector(".ux4g-combobox-control");
        const input = combobox.querySelector("[ux4g-combobox-search]");
        if (control && control.tagName === "BUTTON") control.setAttribute("aria-expanded", "false");
        if (input) input.setAttribute("aria-expanded", "false");
      }
    });
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      const openComboboxes = document.querySelectorAll(".ux4g-combobox.is-open");
      openComboboxes.forEach((combobox) => {
        combobox.classList.remove("is-open");
        const control = combobox.querySelector(".ux4g-combobox-control");
        const input = combobox.querySelector("[ux4g-combobox-search]");
        if (control && control.tagName === "BUTTON") control.setAttribute("aria-expanded", "false");
        if (input) input.setAttribute("aria-expanded", "false");
      });
    }
  });
});


/* ========================================================= ux4g Tabs ========================================================= */


(function (global) {
  'use strict';

  class UX4GTab {

    constructor(rootEl, options = {}) {
      if (!rootEl) return;
      if (rootEl.__ux4gTabInstance) return rootEl.__ux4gTabInstance;

      this.root = rootEl;
      this.options = Object.assign({ onChange: null }, options);
      rootEl.__ux4gTabInstance = this;

      this._detectConfig();
      this._cleanInitialState();
      this._bindEvents();
    }

    /* Detect elements */
    _detectConfig() {
      this.list = this.root.querySelector('.ux4g-tab-list');
      if (!this.list) {
        this.items = [];
        this.moreBtns = [];
        this.panels = [];
        return;
      }
      this.items = Array.from(
        this.list.querySelectorAll('.ux4g-tab-item:not(.ux4g-tab-more)')
      );
      this.moreBtns = Array.from(
        this.list.querySelectorAll('.ux4g-tab-more')
      );
      this.panels = Array.from(
        this.root.querySelectorAll('.ux4g-tab-panel')
      );
    }

    /* Clean any static open state */
    _cleanInitialState() {
      this.root.querySelectorAll('.ux4g-tab-dropdown-list.is-open')
        .forEach(d => d.classList.remove('is-open'));
    }

    /* Bind events */
    _bindEvents() {

      /* Regular tabs */
      this.items.forEach(item => {
        item.addEventListener('click', () => this._activateItem(item));
        item.addEventListener('keydown', (e) => {
          if (e.key === 'Enter') {
            e.preventDefault();
            this._activateItem(item);
          }
        });
      });

      /* More dropdown */
      this.moreBtns.forEach(moreBtn => {
        const dropdown = moreBtn.querySelector('.ux4g-tab-dropdown-list');
        if (!dropdown) return;

        moreBtn.addEventListener('click', (e) => {
          const isDropdownClick = e.target.closest('.ux4g-tab-dropdown-list');

          if (!isDropdownClick) {
            e.stopPropagation();
            this._toggleDropdown(dropdown);
          }
        });

        // Add Enter key support to open dropdown
        moreBtn.addEventListener('keydown', (e) => {
          if (e.key === 'Enter') {
            e.preventDefault();
            const isDropdownClick = e.target.closest('.ux4g-tab-dropdown-list');
            if (!isDropdownClick) {
              e.stopPropagation();
              this._toggleDropdown(dropdown);
            }
          }
        });

        dropdown.querySelectorAll('.ux4g-tab-dropdown-item, .ux4g-list-item')
          .forEach(dItem => {
            dItem.addEventListener('click', (e) => {
              e.stopPropagation();
              this._activateDropdownItem(dItem, moreBtn);
            });
            dItem.addEventListener('keydown', (e) => {
              if (e.key === 'Enter') {
                e.preventDefault();
                e.stopPropagation();
                this._activateDropdownItem(dItem, moreBtn);
              }
            });
          });

        // Also ensure ANY click inside the dropdown list closes it
        dropdown.addEventListener('click', (e) => {
          this._closeAllDropdowns();
        });
      });

      /* Outside click (scoped) */
      document.addEventListener('click', (e) => {
        if (!e.target.closest('.ux4g-tab-more')) {
          this._closeAllDropdowns();
        }
      });
    }

    /* Activate normal tab */
    _activateItem(item) {
      if (item.classList.contains('ux4g-tab-item-disabled')) return;

      this._resetActive();
      item.classList.add('active', 'is-active');

      const panelId = item.dataset.panel;
      if (panelId) this._showPanel(panelId);

      this._closeAllDropdowns();
      this._emitChange(panelId);
    }

    /* Activate dropdown item */
    _activateDropdownItem(dItem, moreBtn) {
      this._resetActive();

      moreBtn.classList.add('active', 'is-active');
      dItem.classList.add('active', 'is-active');

      const panelId = dItem.dataset.panel;
      if (panelId) this._showPanel(panelId);

      this._closeAllDropdowns();
      this._emitChange(panelId);
    }

    /* Reset active states */
    _resetActive() {
      if (this.list) {
        this.list.querySelectorAll('.ux4g-tab-item')
          .forEach(i => i.classList.remove('active', 'is-active'));
      }

      this.root.querySelectorAll('.ux4g-tab-dropdown-item, .ux4g-list-item')
        .forEach(i => i.classList.remove('active', 'is-active'));
    }

    /* Show panel */
    _showPanel(panelId) {
      this.panels.forEach(p => p.classList.remove('active', 'is-active'));
      const target = this.root.querySelector('#' + panelId);
      if (target) target.classList.add('active', 'is-active');
    }

    /* Toggle dropdown */
    _toggleDropdown(dropdown) {
      const isOpen = dropdown.classList.contains('is-open');
      this._closeAllDropdowns();

      if (!isOpen) {
        dropdown.classList.add('is-open');
      }
    }

    /* Close dropdowns (scoped globally to all tabs) */
    _closeAllDropdowns() {
      document.querySelectorAll('.ux4g-tab-dropdown-list.is-open')
        .forEach(d => d.classList.remove('is-open'));
    }

    /* Emit change */
    _emitChange(panelId) {
      if (typeof this.options.onChange === 'function') {
        this.options.onChange(panelId);
      }
    }

    /* Init all */
    static initAll(scope = document) {
      return Array.from(scope.querySelectorAll('[data-ux4g-tab]'))
        .map(el => new UX4GTab(el));
    }
  }

  global.UX4GTab = UX4GTab;

})(window);

/* Auto init */
window.ux4gCustomInitList = window.ux4gCustomInitList || []; window.ux4gCustomInitList.push(() => {
  UX4GTab.initAll();
});

/* ========================================================= slider js ========================================================= */

window.ux4gCustomInitList = window.ux4gCustomInitList || []; window.ux4gCustomInitList.push(() => {

  /* =========================================================
     HELPERS
  ========================================================= */

  const pct = (val, min, max) => ((val - min) / (max - min)) * 100;

  const buildSteps = (container, min, max, step) => {
    if (!container) return;

    let html = "";

    for (let v = min; v <= max; v += step) {
      const p = pct(v, min, max);

      html += `
        <div class="ux4g-slider-step" data-value="${v}" style="left:${p}%">
          <span class="ux4g-slider-step-mark"></span>
          <span class="ux4g-slider-step-label">${v}</span>
        </div>
      `;
    }

    container.innerHTML = html;
  };

  /* =========================================================
     SINGLE SLIDERS
  ========================================================= */

  const singleSliders = document.querySelectorAll(
    ".ux4g-slider:not(.ux4g-slider-dual)"
  );

  singleSliders.forEach((slider) => {

    const input = slider.querySelector(".ux4g-slider-input");
    const fill = slider.querySelector(".ux4g-slider-fill");
    const thumb = slider.querySelector(".ux4g-slider-thumb");
    const steps = slider.querySelector(".ux4g-slider-steps");

    if (!input) return;

    const valueBox =
      slider.closest(".ux4g-slider-field")?.querySelector(
        ".ux4g-slider-range-box"
      );
    const valueBadge =
      slider.closest(".ux4g-slider-field")?.querySelector(
        ".ux4g-slider-value-badge"
      );

    const min = parseFloat(input.min) || 0;
    const max = parseFloat(input.max) || 100;
    const step = parseFloat(input.step) || 10;

    /* Helper: Make badge editable */
    const initBadgeEdit = (badge, targetInput) => {
      if (!badge) return;
      badge.setAttribute("contenteditable", "true");
      badge.style.cursor = "text";

      // Strict number only entry
      badge.addEventListener("keypress", (e) => {
        if (!/[0-9]/.test(e.key) && e.key !== "Enter") {
          e.preventDefault();
        }
      });

      badge.addEventListener("keydown", (e) => {
        if (e.key === "Enter") {
          e.preventDefault();
          badge.blur();
        }
      });

      badge.addEventListener("blur", () => {
        let raw = badge.textContent.replace(/[^0-9]/g, "");
        let val = parseInt(raw, 10);
        if (isNaN(val)) val = parseFloat(targetInput.value);

        // Clamp 0-100
        val = Math.min(max, Math.max(min, val));

        targetInput.value = val;
        // Trigger input event to update everything
        targetInput.dispatchEvent(new Event("input"));

        // Re-apply suffix if needed
        const suffix = badge.textContent.includes("%") ? "%" : "";
        badge.textContent = val + suffix;
      });
    };

    if (valueBox) initBadgeEdit(valueBox, input);

    /* Build Steps */

    buildSteps(steps, min, max, step);

    const stepEls = slider.querySelectorAll(".ux4g-slider-step");

    const update = () => {

      const val = parseFloat(input.value);
      const p = pct(val, min, max);

      if (fill) fill.style.width = p + "%";
      if (thumb) thumb.style.left = p + "%";

      // Only update text containers if not currently editing
      const containers = [valueBadge, valueBox];
      containers.forEach(container => {
        if (container && document.activeElement !== container) {
          const suffix = container.textContent.includes("%") ? "%" : "";
          container.textContent = val + suffix;
        }
      });

      stepEls.forEach((el) => {
        const sv = parseFloat(el.dataset.value);
        el.classList.toggle("is-active", sv <= val);
      });

    };

    input.addEventListener("input", update);

    update();

  });

  /* =========================================================
     DUAL RANGE SLIDERS
  ========================================================= */

  const dualSliders = document.querySelectorAll(".ux4g-slider-dual");

  dualSliders.forEach((slider) => {

    const inputMin = slider.querySelector(".ux4g-slider-input-min");
    const inputMax = slider.querySelector(".ux4g-slider-input-max");

    const fill = slider.querySelector(".ux4g-slider-fill");
    const thumbMin = slider.querySelector(".ux4g-slider-thumb-min");
    const thumbMax = slider.querySelector(".ux4g-slider-thumb-max");

    const steps = slider.querySelector(".ux4g-slider-steps");

    const field = slider.closest(".ux4g-slider-field");

    const minBox = field?.querySelector(".ux4g-slider-range-box:first-of-type, .ux4g-slider-range-box");
    // Actually better to select all and differentiate if multiple exist
    const rangeBoxes = field?.querySelectorAll(".ux4g-slider-range-box");
    const valueBadges = field?.querySelectorAll(".ux4g-slider-value-badge");

    if (!inputMin || !inputMax) return;

    const min = parseFloat(inputMin.min) || 0;
    const max = parseFloat(inputMin.max) || 100;
    const step = parseFloat(inputMin.step) || 10;

    /* Helper: Make box editable */
    const initBoxEdit = (badge, targetInput) => {
      if (!badge) return;
      badge.setAttribute("contenteditable", "true");
      badge.style.cursor = "text";

      // Strict number only entry
      badge.addEventListener("keypress", (e) => {
        if (!/[0-9]/.test(e.key) && e.key !== "Enter") {
          e.preventDefault();
        }
      });

      badge.addEventListener("keydown", (e) => {
        if (e.key === "Enter") {
          e.preventDefault();
          badge.blur();
        }
      });

      badge.addEventListener("blur", () => {
        let raw = badge.textContent.replace(/[^0-9]/g, "");
        let val = parseInt(raw, 10);
        if (isNaN(val)) val = parseFloat(targetInput.value);

        // Clamp min-max
        val = Math.min(max, Math.max(min, val));

        targetInput.value = val;
        // Trigger change to validate cross limits
        targetInput.dispatchEvent(new Event("input"));

        // Re-apply suffix
        const suffix = badge.textContent.includes("%") ? "%" : "";
        badge.textContent = val + suffix;
      });
    };

    if (rangeBoxes?.[0]) initBoxEdit(rangeBoxes[0], inputMin);
    if (rangeBoxes?.[1]) initBoxEdit(rangeBoxes[1], inputMax);

    buildSteps(steps, min, max, step);

    const stepEls = slider.querySelectorAll(".ux4g-slider-step");

    const update = (e) => {

      let vMin = parseFloat(inputMin.value);
      let vMax = parseFloat(inputMax.value);

      /* Prevent crossing */

      if (e?.target === inputMin && vMin >= vMax) {
        inputMin.value = vMax - step;
        vMin = parseFloat(inputMin.value);
      }

      if (e?.target === inputMax && vMax <= vMin) {
        inputMax.value = vMin + step;
        vMax = parseFloat(inputMax.value);
      }

      const pMin = pct(vMin, min, max);
      const pMax = pct(vMax, min, max);

      if (fill) {
        fill.style.left = pMin + "%";
        fill.style.width = (pMax - pMin) + "%";
      }

      if (thumbMin) thumbMin.style.left = pMin + "%";
      if (thumbMax) thumbMax.style.left = pMax + "%";

      // Sync dual badges
      if (valueBadges?.[0] && document.activeElement !== valueBadges[0]) {
        valueBadges[0].textContent = vMin + (valueBadges[0].textContent.includes("%") ? "%" : "");
      }
      if (valueBadges?.[1] && document.activeElement !== valueBadges[1]) {
        valueBadges[1].textContent = vMax + (valueBadges[1].textContent.includes("%") ? "%" : "");
      }

      // Sync dual boxes
      if (rangeBoxes?.[0] && document.activeElement !== rangeBoxes[0]) {
        rangeBoxes[0].textContent = vMin + (rangeBoxes[0].textContent.includes("%") ? "%" : "");
      }
      if (rangeBoxes?.[1] && document.activeElement !== rangeBoxes[1]) {
        rangeBoxes[1].textContent = vMax + (rangeBoxes[1].textContent.includes("%") ? "%" : "");
      }

      /* Thumb overlap fix */

      if (pMax - pMin < 10) {

        inputMin.style.zIndex = pMin > 50 ? "5" : "3";
        inputMax.style.zIndex = pMin > 50 ? "3" : "5";

      } else {

        inputMin.style.zIndex = "";
        inputMax.style.zIndex = "";

      }

      /* Active steps */

      stepEls.forEach((el) => {

        const sv = parseFloat(el.dataset.value);

        el.classList.toggle(
          "is-active",
          sv >= vMin && sv <= vMax
        );

      });

    };

    inputMin.addEventListener("input", update);
    inputMax.addEventListener("input", update);

    update();

  });

});


/* ========================================================= context alert js ========================================================= */
/* Trigger Toast alerts via data attributes */
window.ux4gCustomInitList = window.ux4gCustomInitList || []; window.ux4gCustomInitList.push(() => {
  document.querySelectorAll('[data-ux4g-toggle="toast"]').forEach(btn => {
    btn.addEventListener('click', () => {
      const position = btn.dataset.ux4gPosition || 'top-right';
      const variant = btn.dataset.ux4gVariant || btn.dataset.ux4gStatus;
      const title = btn.dataset.ux4gTitle;
      const body = btn.dataset.ux4gBody;
      showContextAlert(position, variant, title, body);
    });
  });
});

let alertCount = 0;

/**
 * Shows a Context Alert (Toast) in the specified position with animation
 * @param {string} position - 'top-left', 'top-right', 'bottom-left', or 'bottom-right'
 * @param {string} [variant] - Optional: 'info', 'success', 'warning', 'error', 'none'
 * @param {string} [customTitle] - Optional custom title text
 * @param {string} [customBody] - Optional custom body text
 */
function showContextAlert(position, variant, customTitle, customBody) {
  const statuses = {
    'info': { icon: 'info', title: 'Info ' },
    'success': { icon: 'check_circle', title: 'Success ' },
    'warning': { icon: 'warning', title: 'Warning ' },
    'error': { icon: 'error', title: 'Error ' },
    'none': { icon: null, title: 'Alert Title' }
  };

  // Determine type (use variant, or cycle through if not provided)
  const types = Object.keys(statuses).filter(t => t !== 'none');
  const type = variant || types[alertCount++ % types.length];
  const status = statuses[type] || statuses['info'];

  const title = customTitle || status.title;
  const bodyText = customBody || `This is a ${type} alert shown at the ${position.replace('-', ' ')} corner.`;

  const containerId = `ux4g-alert-container-${position}`;
  let container = document.getElementById(containerId);

  if (!container) {
    container = document.createElement('div');
    container.id = containerId;
    container.className = `ux4g-alert-container ux4g-alert-${position}`;
    document.body.appendChild(container);
  }

  const alert = document.createElement('div');
  const animationClass = position.includes('left') ? 'ux4g-animate-left' : 'ux4g-animate-right';

  // Apply correct status class (fallback to info if variant is 'none' for styling)
  const statusClass = type === 'none' ? 'info' : type;
  alert.className = `ux4g-context-alert ux4g-alert-${statusClass} ${animationClass}`;

  // Icon Logic: None means no icon HTML
  const iconHtml = status.icon ? `<i class="ux4g-icon ux4g-alert-icon">${status.icon}</i>` : '';

  alert.innerHTML = `
        ${iconHtml}
        <span class="ux4g-alert-title">${title}</span>
        <div class="ux4g-alert-actions">
            <button class="ux4g-alert-close" onclick="closeContextAlert(this)">
                <i class="ux4g-icon">close</i>
            </button>
        </div>
        <div class="ux4g-alert-message">${bodyText}</div>
    `;

  if (position.includes('bottom')) {
    container.insertBefore(alert, container.firstChild);
  } else {
    container.appendChild(alert);
  }

  setTimeout(() => {
    if (alert.parentNode) closeAlertWithAnimation(alert);
  }, 5000);
}

/**
 * Handles manual close click
 */
function closeContextAlert(button) {
  const alert = button.closest('.ux4g-context-alert');
  if (alert) {
    closeAlertWithAnimation(alert);
  }
}

/**
 * Closes an alert with a slide-out animation
 */
function closeAlertWithAnimation(alert) {
  if (!alert) return;

  const isLeft = alert.classList.contains('ux4g-animate-left');
  alert.style.transition = 'all 0.4s cubic-bezier(0.16, 1, 0.3, 1)';
  alert.style.transform = isLeft ? 'translateX(-100%)' : 'translateX(100%)';
  alert.style.opacity = '0';

  // Remove element after animation completes
  setTimeout(() => {
    if (alert.parentNode) {
      alert.parentNode.removeChild(alert);
    }
  }, 400);
}


/* ========================================================= pagination js ========================================================= */
window.ux4gCustomInitList = window.ux4gCustomInitList || []; window.ux4gCustomInitList.push(() => {
  const pageSelects = document.querySelectorAll(".ux4g-page-size select");

  pageSelects.forEach(select => {
    const wrapper = select.closest(".ux4g-page-size-select-wrapper");

    const updateState = () => {
      if (wrapper) {
        // If there's a selected option that isn't empty (or for simplicity, any selection)
        if (select.value) {
          wrapper.classList.add("has-value");
        } else {
          wrapper.classList.remove("has-value");
        }
      }
    };

    select.addEventListener("change", updateState);
    // Do not run on load, so we show the placeholder icon by default
  });
});


// NPS and Emoji Button interactions
// Using global event delegation so it works seamlessly with dynamic HTML, React, Angular, etc.
document.addEventListener('click', (e) => {
  // 1. NPS interaction
  const npsBtn = e.target.closest('.feedback-nps-button');
  if (npsBtn) {
    const container = npsBtn.closest('.ux4g-feedback-nps-wrapper') || npsBtn.parentElement;
    const siblings = Array.from(container.querySelectorAll('.feedback-nps-button'));
    const clickedIndex = siblings.indexOf(npsBtn);

    const isHighestActive = npsBtn.classList.contains('active') &&
      (clickedIndex === siblings.length - 1 || !siblings[clickedIndex + 1]?.classList.contains('active'));

    if (isHighestActive) {
      siblings.forEach(s => s.classList.remove('active'));
      container.removeAttribute('data-nps-rating');
    } else {
      container.setAttribute('data-nps-rating', clickedIndex);
      siblings.forEach((s, i) => {
        if (i <= clickedIndex) {
          s.classList.add('active');
        } else {
          s.classList.remove('active');
        }
      });
    }
    return;
  }

  // 2. Emoji interaction
  const emojiBtn = e.target.closest('.feedback-emoji-button');
  if (emojiBtn) {
    const wasActive = emojiBtn.classList.contains('active');
    const container = emojiBtn.closest('.ux4g-d-flex') || document;
    container.querySelectorAll('.feedback-emoji-button').forEach(b => b.classList.remove('active'));

    if (!wasActive) {
      emojiBtn.classList.add('active');
    }
    return;
  }

  // 3. Star interaction
  const starBtn = e.target.closest('.ux4g-feedback-star');
  if (starBtn) {
    const container = starBtn.parentElement;
    const siblings = Array.from(container.querySelectorAll('.ux4g-feedback-star'));
    const clickedIndex = siblings.indexOf(starBtn);

    const isOnlyActive = starBtn.classList.contains('active') &&
      (clickedIndex === siblings.length - 1 || !siblings[clickedIndex + 1]?.classList.contains('active'));

    if (isOnlyActive) {
      siblings.forEach(s => s.classList.remove('active'));
      container.removeAttribute('data-rating');
    } else {
      container.setAttribute('data-rating', clickedIndex + 1);
      siblings.forEach((s, i) => {
        if (i <= clickedIndex) {
          s.classList.add('active');
        } else {
          s.classList.remove('active');
        }
      });
    }

    // Toggle Submit button state based on rating
    const feedbackCard = starBtn.closest('.ux4g-feedback');
    if (feedbackCard) {
      const submitBtn = feedbackCard.querySelector('.ux4g-btn-primary');
      if (submitBtn) {
        const hasRating = container.hasAttribute('data-rating') && container.getAttribute('data-rating') !== '';
        if (hasRating) {
          submitBtn.removeAttribute('disabled');
        } else {
          submitBtn.setAttribute('disabled', '');
        }
      }
    }
    return;
  }

  // 4. Submit button showing success card delegator
  const submitBtn = e.target.closest('.ux4g-feedback:not(.ux4g-text-center) .ux4g-btn-primary');
  if (submitBtn) {
    const ratingCard = submitBtn.closest('.ux4g-feedback');
    if (ratingCard) {
      const container = ratingCard.parentElement;
      if (container) {
        const successCard = container.querySelector('.ux4g-feedback.ux4g-text-center');
        if (successCard) {
          e.preventDefault();
          ratingCard.style.display = 'none';
          successCard.style.display = 'flex';
          return;
        }
      }
    }
  }

  // 5. Close button redirect delegator
  const closeBtn = e.target.closest('.feedback-submitted-close-btn');
  if (closeBtn) {
    e.preventDefault();
    window.location.href = './global-service-discovery.html';
    return;
  }

  // 6. Submit and Skip Reset interaction
  const resetBtn = e.target.closest('.ux4g-feedback .ux4g-btn-primary, .ux4g-feedback .ux4g-btn-text-primary');
  if (resetBtn) {
    const feedbackContainer = resetBtn.closest('.ux4g-feedback');
    if (feedbackContainer) {
      feedbackContainer.querySelectorAll('textarea').forEach(textarea => {
        textarea.value = '';
      });
      feedbackContainer.querySelectorAll('.active').forEach(activeEl => {
        activeEl.classList.remove('active');
      });
    }
  }
});

// rating enabled btn js
window.ux4gCustomInitList = window.ux4gCustomInitList || []; window.ux4gCustomInitList.push(() => {
  // Initialize all feedback cards' submit buttons on load/open
  const syncAllFeedbackSubmitButtons = () => {
    document.querySelectorAll('.ux4g-feedback').forEach(feedbackCard => {
      const starContainer = feedbackCard.querySelector('[data-rating]');
      const submitBtn = feedbackCard.querySelector('.ux4g-btn-primary');
      if (submitBtn) {
        const hasRating = starContainer && starContainer.hasAttribute('data-rating') && starContainer.getAttribute('data-rating') !== '';
        if (hasRating) {
          submitBtn.removeAttribute('disabled');
        } else {
          submitBtn.setAttribute('disabled', '');
        }
      }
    });
  };

  syncAllFeedbackSubmitButtons();

  // Also sync when modals/popups are shown/opened
  document.addEventListener('click', (e) => {
    const trigger = e.target.closest('[data-modal-target], [data-target], .open-modal-btn, .modal-backdrop-btn-trigger');
    if (trigger) {
      setTimeout(syncAllFeedbackSubmitButtons, 300);
    }
  });
});


/* ========================================================= carousel js ========================================================= */
window.ux4gCustomInitList = window.ux4gCustomInitList || []; window.ux4gCustomInitList.push(() => {
  const carousels = document.querySelectorAll(".ux4g-carousel");

  carousels.forEach(carousel => {
    const slidesContainer = carousel.querySelector(".ux4g-carousel-slides");
    const slides = carousel.querySelectorAll(".ux4g-carousel-slide");
    const slideCount = slides.length;
    const prevBtn = carousel.querySelector(".ux4g-carousel-arrow-prev");
    const nextBtn = carousel.querySelector(".ux4g-carousel-arrow-next");
    const dots = carousel.querySelectorAll(".ux4g-carousel-dot");

    if (slideCount === 0) return;

    let currentIndex = 0;

    // Initialize: find if any slide is already marked as active
    slides.forEach((slide, index) => {
      if (slide.classList.contains("is-active")) {
        currentIndex = index;
      }
    });

    const updateCarousel = (index) => {
      // Handle looping
      if (index < 0) {
        index = slideCount - 1;
      } else if (index >= slideCount) {
        index = 0;
      }

      currentIndex = index;

      // Move slides
      if (slidesContainer) {
        slidesContainer.style.transform = `translateX(-${currentIndex * 100}%)`;
      }

      // Update slides active state
      slides.forEach((slide, i) => {
        slide.classList.toggle("is-active", i === currentIndex);
        slide.setAttribute("aria-hidden", i !== currentIndex);
      });

      // Update dots active state
      dots.forEach((dot, i) => {
        dot.classList.toggle("is-active", i === currentIndex);
        dot.setAttribute("aria-current", i === currentIndex ? "step" : "false");
      });
    };

    // Event Listeners
    if (prevBtn) {
      prevBtn.addEventListener("click", (e) => {
        e.preventDefault();
        updateCarousel(currentIndex - 1);
      });
    }

    if (nextBtn) {
      nextBtn.addEventListener("click", (e) => {
        e.preventDefault();
        updateCarousel(currentIndex + 1);
      });
    }

    dots.forEach((dot, index) => {
      dot.addEventListener("click", (e) => {
        e.preventDefault();
        updateCarousel(index);
      });
    });

    // Initial update to ensure everything is synced
    updateCarousel(currentIndex);
  });
});

/* ========================================================= range slider js ========================================================= */
document.addEventListener("input", (e) => {
  if (e.target.classList.contains("ux4g-slider-input")) {
    const sliderField = e.target.closest(".ux4g-slider-field");
    const slider = e.target.closest(".ux4g-slider");
    if (!slider) return;

    const isDual = slider.classList.contains("ux4g-slider-dual");
    const fill = slider.querySelector(".ux4g-slider-fill");

    if (!isDual) {
      const thumb = slider.querySelector(".ux4g-slider-thumb");
      const percent = ((e.target.value - e.target.min) / (e.target.max - e.target.min)) * 100;
      if (fill) fill.style.width = percent + "%";
      if (thumb) thumb.style.left = percent + "%";

      if (sliderField) {
        const badge = sliderField.querySelector(".ux4g-slider-value-badge");
        if (badge) badge.textContent = e.target.value + "%";
      }
    } else {
      const inputMin = slider.querySelector(".ux4g-slider-input-min");
      const inputMax = slider.querySelector(".ux4g-slider-input-max");
      const thumbMin = slider.querySelector(".ux4g-slider-thumb-min");
      const thumbMax = slider.querySelector(".ux4g-slider-thumb-max");

      let min = parseFloat(inputMin.value);
      let max = parseFloat(inputMax.value);
      const rangeMin = parseFloat(inputMin.min);
      const rangeMax = parseFloat(inputMin.max);

      if (e.target.classList.contains("ux4g-slider-input-min")) {
        if (min > max) {
          min = max;
          inputMin.value = min;
        }
      } else {
        if (max < min) {
          max = min;
          inputMax.value = max;
        }
      }

      const left = ((min - rangeMin) / (rangeMax - rangeMin)) * 100;
      const width = ((max - min) / (rangeMax - rangeMin)) * 100;

      if (fill) {
        fill.style.left = left + "%";
        fill.style.width = width + "%";
      }
      if (thumbMin) thumbMin.style.left = left + "%";
      if (thumbMax) thumbMax.style.left = (left + width) + "%";

      if (sliderField) {
        const badges = sliderField.querySelectorAll(".ux4g-slider-value-badge");
        if (badges.length >= 2) {
          badges[0].textContent = min + "%";
          badges[1].textContent = max + "%";
        }
      }
    }
  }
});


/********************************* UX4G DatePicker & TimePicker JS ***********************************/

(function (global) {
  "use strict";

  const makeKeyboardClickable = (el) => {
    if (!el) return;
    if (el.tagName !== 'BUTTON' && el.tagName !== 'INPUT') {
      if (!el.hasAttribute('tabindex')) el.setAttribute('tabindex', '0');
      el.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          el.click();
        }
      });
    }
  };

  // Shared Backdrop
  let backdrop = document.querySelector('.ux4g-date-picker-backdrop');
  const getBackdrop = () => {
    if (!backdrop) {
      backdrop = document.createElement('div');
      backdrop.className = 'ux4g-date-picker-backdrop';
      document.body.appendChild(backdrop);
    }
    return backdrop;
  };

  const isMobile = () => window.innerWidth <= 576;
  const initializedDatePickers = new WeakSet();
  const initializedRangeDatePickers = new WeakSet();
  const initializedTimePickers = new WeakSet();

  const adjustDropdownPosition = (dropdown, container) => {
    if (!dropdown || !container) return;
    const viewportGap = 8;
    dropdown.style.top = '';
    dropdown.style.bottom = '';
    dropdown.style.left = '';
    dropdown.style.right = '';
    dropdown.style.width = '';
    dropdown.style.maxHeight = '';
    dropdown.style.overflowX = '';
    dropdown.style.overflowY = '';
    dropdown.style.marginTop = '';
    dropdown.style.marginBottom = '';
    const containerRect = container.getBoundingClientRect();
    const dropdownRect = dropdown.getBoundingClientRect();
    const windowHeight = window.innerHeight || document.documentElement.clientHeight;
    const windowWidth = window.innerWidth || document.documentElement.clientWidth;

    // Anchor to the actual input rather than the container's bounding box. Using
    // the container edge can create artificial gaps when the container is made
    // taller for layout reasons.
    const trigger = container.querySelector('.ux4g-date-picker-input:focus, .ux4g-time-picker-input:focus') ||
                      container.querySelector('.ux4g-date-picker-input, .ux4g-time-picker-input') ||
                      container;
    const triggerRect = trigger.getBoundingClientRect();

    const spaceBelow = windowHeight - triggerRect.bottom - viewportGap;
    const spaceAbove = triggerRect.top - viewportGap;
    const openAbove = spaceBelow < dropdownRect.height && spaceAbove > spaceBelow;
    const width = Math.min(containerRect.width, windowWidth - (viewportGap * 2));
    const left = Math.min(
      Math.max(containerRect.left, viewportGap),
      Math.max(windowWidth - width - viewportGap, viewportGap)
    );

    // Fixed positioning escapes overflow/scroll containers (including Storybook
    // preview wrappers) while keeping the popup anchored to its input.
    dropdown.style.position = 'fixed';
    dropdown.style.width = `${width}px`;
    dropdown.style.left = `${left}px`;
    dropdown.style.right = 'auto';
    if (openAbove) {
      const topPos = Math.max(viewportGap, triggerRect.top - dropdownRect.height - viewportGap);
      dropdown.style.top = `${topPos}px`;
    } else {
      dropdown.style.top = `${triggerRect.bottom + viewportGap}px`;
    }
    dropdown.style.bottom = 'auto';
  };

  const resetDropdownPosition = (dropdown) => {
    if (!dropdown) return;
    ['position', 'top', 'bottom', 'left', 'right', 'width', 'maxHeight',
      'overflowX', 'overflowY', 'marginTop', 'marginBottom']
      .forEach(property => { dropdown.style[property] = ''; });
  };

  const repositionOpenDropdowns = () => {
    if (isMobile()) return;
    document.querySelectorAll('.ux4g-date-picker-dropdown.is-open, .ux4g-time-picker-dropdown.is-open')
      .forEach(dropdown => {
        const container = dropdown.closest('.ux4g-date-range-picker, .ux4g-date-picker-container, .ux4g-time-picker-container');
        adjustDropdownPosition(dropdown, container);
      });
  };

  let rafDropdown = null;
  const throttledRepositionOpenDropdowns = () => {
    if (rafDropdown) return;
    rafDropdown = requestAnimationFrame(() => {
      repositionOpenDropdowns();
      rafDropdown = null;
    });
  };

  window.addEventListener('resize', throttledRepositionOpenDropdowns);
  window.addEventListener('scroll', throttledRepositionOpenDropdowns, { capture: true, passive: true });

  class DatePicker {
    constructor(container) {
      this.container = container;
      this.input = container.querySelector('.ux4g-date-picker-input');
      this.dropdown = container.querySelector('.ux4g-date-picker-dropdown');
      this.calendarGrid = container.querySelector('.ux4g-date-picker-grid');
      this.monthLabel = container.querySelector('.ux4g-date-picker-current');

      const navBtns = container.querySelectorAll('.ux4g-date-picker-nav-btn');
      this.prevBtn = navBtns[0];
      this.nextBtn = navBtns[1];

      this.confirmBtn = container.querySelector('.ux4g-btn-primary');
      this.cancelBtn = container.querySelector('.ux4g-btn-outline-neutral');

      this.currentDate = new Date();
      this.viewDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth(), 1);
      this.selectedDate = null;
      this.tempSelectedDate = null;

      this.isSelectingYearMonth = false;

      this._init();
    }

    _init() {
      if (!this.input || !this.dropdown) return;

      if (this.input.value) {
        const parts = this.input.value.split('/');
        if (parts.length === 3) {
          this.selectedDate = new Date(parts[2], parts[1] - 1, parts[0]);
          this.viewDate = new Date(parts[2], parts[1] - 1, 1);
          this.tempSelectedDate = new Date(this.selectedDate);
        }
      }

      // Keyboard accessibility
      this.input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          this.open();
        }
      });

      this.input.addEventListener('focus', (e) => {
        this.open();
      });

      this.input.addEventListener('click', (e) => {
        e.stopPropagation();
        this.open();
      });

      if (this.prevBtn) {
        makeKeyboardClickable(this.prevBtn);
        this.prevBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          if (this.isSelectingYearMonth) {
            this.changeYearRange(-8);
          } else {
            this.changeMonth(-1);
          }
        });
      }

      if (this.nextBtn) {
        makeKeyboardClickable(this.nextBtn);
        this.nextBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          if (this.isSelectingYearMonth) {
            this.changeYearRange(8);
          } else {
            this.changeMonth(1);
          }
        });
      }

      if (this.monthLabel) {
        makeKeyboardClickable(this.monthLabel);
        this.monthLabel.addEventListener('click', (e) => {
          e.stopPropagation();
          this.isSelectingYearMonth = !this.isSelectingYearMonth;
          this.render();
          setTimeout(() => {
            if (this.isSelectingYearMonth) {
              const calendarContainer = this.container.querySelector('.ux4g-date-picker-calendar');
              let el = calendarContainer.querySelector('.ux4g-date-picker-year-item.is-selected') || calendarContainer.querySelector('.ux4g-date-picker-year-item');
              if (el) el.focus();
            } else {
              let el = this.calendarGrid.querySelector('.is-selected') || this.calendarGrid.querySelector('.is-today') || this.calendarGrid.querySelector('.ux4g-date-picker-day:not(.is-muted)');
              if (el) el.focus();
            }
          }, 0);
        });
      }

      if (this.confirmBtn) {
        makeKeyboardClickable(this.confirmBtn);
        this.confirmBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          if (this.isSelectingYearMonth) {
            this.isSelectingYearMonth = false;
            this.render();
            setTimeout(() => {
              let el = this.calendarGrid.querySelector('.is-selected') || this.calendarGrid.querySelector('.is-today') || this.calendarGrid.querySelector('.ux4g-date-picker-day:not(.is-muted)');
              if (el) el.focus();
            }, 0);
          } else {
            this.confirmSelection();
          }
        });
      }

      if (this.cancelBtn) {
        makeKeyboardClickable(this.cancelBtn);
        this.cancelBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          this.cancelSelection();
        });
      }

      document.addEventListener('click', (e) => {
        if (!this.container.contains(e.target) && !getBackdrop().contains(e.target)) {
          this.close();
        }
      });

      getBackdrop().addEventListener('click', () => {
        this.close();
      });

      this.render();
    }

    open() {
      if (this.dropdown) {
        this.tempSelectedDate = this.selectedDate ? new Date(this.selectedDate) : null;
        this.isSelectingYearMonth = false;
        this.dropdown.classList.add('is-open');
        if (isMobile()) {
          getBackdrop().classList.add('is-active');
          document.body.style.overflow = 'hidden';
        } else {
          adjustDropdownPosition(this.dropdown, this.container);
        }
        this.render();
      }
    }

    close() {
      if (this.dropdown) {
        this.dropdown.classList.remove('is-open');
        resetDropdownPosition(this.dropdown);
        getBackdrop().classList.remove('is-active');
        document.body.style.overflow = '';
      }
    }

    confirmSelection() {
      this.selectedDate = this.tempSelectedDate ? new Date(this.tempSelectedDate) : null;
      if (this.selectedDate) {
        const day = String(this.selectedDate.getDate()).padStart(2, '0');
        const month = String(this.selectedDate.getMonth() + 1).padStart(2, '0');
        const year = this.selectedDate.getFullYear();
        this.input.value = `${day}/${month}/${year}`;
      } else {
        this.input.value = '';
      }
      this.close();
    }

    cancelSelection() {
      this.tempSelectedDate = this.selectedDate ? new Date(this.selectedDate) : null;
      this.close();
    }

    changeMonth(delta) {
      this.viewDate.setMonth(this.viewDate.getMonth() + delta);
      this.render();
    }

    changeYearRange(delta) {
      this.viewDate.setFullYear(this.viewDate.getFullYear() + delta);
      this.render();
    }

    render() {
      if (this.isSelectingYearMonth) {
        this.renderYearMonthSelection();
      } else {
        this.renderCalendar();
      }
    }

    renderCalendar() {
      const year = this.viewDate.getFullYear();
      const month = this.viewDate.getMonth();
      const monthNames = ["January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"
      ];

      if (this.monthLabel) {
        this.monthLabel.innerHTML = `${monthNames[month]} ${year} <span class="ux4g-icon-outlined ux4g-fs-18">keyboard_arrow_down</span>`;
      }

      const calendarHtml = `
                <div class="ux4g-date-picker-weekdays">
                    <div class="ux4g-date-picker-weekday">Mo</div>
                    <div class="ux4g-date-picker-weekday">Tu</div>
                    <div class="ux4g-date-picker-weekday">We</div>
                    <div class="ux4g-date-picker-weekday">Th</div>
                    <div class="ux4g-date-picker-weekday">Fr</div>
                    <div class="ux4g-date-picker-weekday">Sa</div>
                    <div class="ux4g-date-picker-weekday">Su</div>
                </div>
                <div class="ux4g-date-picker-grid"></div>
            `;

      const calendarContainer = this.container.querySelector('.ux4g-date-picker-calendar');
      calendarContainer.innerHTML = calendarHtml;
      this.calendarGrid = calendarContainer.querySelector('.ux4g-date-picker-grid');

      const firstDayOfMonth = new Date(year, month, 1).getDay();
      const daysInMonth = new Date(year, month + 1, 0).getDate();
      let startDay = firstDayOfMonth === 0 ? 6 : firstDayOfMonth - 1;
      const prevMonthLastDay = new Date(year, month, 0).getDate();

      let html = '';
      for (let i = startDay - 1; i >= 0; i--) {
        html += `<div class="ux4g-date-picker-day is-muted">${prevMonthLastDay - i}</div>`;
      }

      for (let i = 1; i <= daysInMonth; i++) {
        const date = new Date(year, month, i);
        const isToday = date.toDateString() === this.currentDate.toDateString();
        const isSelected = this.tempSelectedDate && date.toDateString() === this.tempSelectedDate.toDateString();

        let classes = 'ux4g-date-picker-day';
        if (isToday) classes += ' is-today';
        if (isSelected) classes += ' is-selected';

        html += `<div class="${classes}" data-date="${i}" tabindex="0">${i}</div>`;
      }

      const totalCells = 42;
      const remainingCells = totalCells - (startDay + daysInMonth);
      for (let i = 1; i <= remainingCells; i++) {
        html += `<div class="ux4g-date-picker-day is-muted">${i}</div>`;
      }

      this.calendarGrid.innerHTML = html;
      this.calendarGrid.querySelectorAll('.ux4g-date-picker-day:not(.is-muted)').forEach(dayEl => {
        makeKeyboardClickable(dayEl);
        dayEl.addEventListener('click', (e) => {
          e.stopPropagation();
          const day = e.target.dataset.date;
          this.selectDate(new Date(year, month, day));
        });
      });

      if (this.confirmBtn) {
        this.confirmBtn.innerHTML = 'Confirm';
        this.confirmBtn.disabled = !this.tempSelectedDate;
      }
    }

    renderYearMonthSelection() {
      const currentYear = this.viewDate.getFullYear();
      const startYear = Math.floor(currentYear / 8) * 8;
      const endYear = startYear + 7;

      if (this.monthLabel) {
        this.monthLabel.innerHTML = `${startYear}-${endYear} <span class="ux4g-icon-outlined ux4g-fs-18">keyboard_arrow_down</span>`;
      }

      let html = '<div class="ux4g-date-picker-selection-view">';
      html += '<div class="ux4g-date-picker-year-grid">';
      for (let y = startYear; y <= endYear; y++) {
        const isSelected = y === this.viewDate.getFullYear();
        html += `<div class="ux4g-date-picker-year-item ${isSelected ? 'is-selected' : ''}" data-year="${y}" tabindex="0">${y}</div>`;
      }
      html += '</div>';

      const monthNamesShort = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
      html += '<div class="ux4g-date-picker-month-grid">';
      monthNamesShort.forEach((m, i) => {
        const isSelected = i === this.viewDate.getMonth();
        html += `<div class="ux4g-date-picker-month-item ${isSelected ? 'is-selected' : ''}" data-month="${i}" tabindex="0">${m}</div>`;
      });
      html += '</div></div>';

      const calendarContainer = this.container.querySelector('.ux4g-date-picker-calendar');
      calendarContainer.innerHTML = html;

      calendarContainer.querySelectorAll('.ux4g-date-picker-year-item').forEach(el => {
        makeKeyboardClickable(el);
        el.addEventListener('click', (e) => {
          e.stopPropagation();
          this.viewDate.setFullYear(parseInt(e.target.dataset.year));
          this.renderYearMonthSelection();
          setTimeout(() => {
            const selectedMonth = this.container.querySelector('.ux4g-date-picker-month-item.is-selected');
            if (selectedMonth) selectedMonth.focus();
          }, 0);
        });
      });

      calendarContainer.querySelectorAll('.ux4g-date-picker-month-item').forEach(el => {
        makeKeyboardClickable(el);
        el.addEventListener('click', (e) => {
          e.stopPropagation();
          this.viewDate.setMonth(parseInt(e.target.dataset.month));
          this.renderYearMonthSelection();
          if (this.confirmBtn) setTimeout(() => this.confirmBtn.focus(), 0);
        });
      });

      if (this.confirmBtn) {
        this.confirmBtn.innerHTML = 'Select date';
        this.confirmBtn.disabled = false;
      }
    }

    selectDate(date) {
      this.tempSelectedDate = date;
      this.render();
      if (this.confirmBtn && !this.confirmBtn.disabled) {
        setTimeout(() => this.confirmBtn.focus(), 0);
      }
    }
  }

  class RangeDatePicker {
    constructor(container) {
      this.container = container;
      this.inputs = container.querySelectorAll('.ux4g-date-picker-input');
      this.dropdown = container.querySelector('.ux4g-date-picker-dropdown');
      this.calendarGrid = container.querySelector('.ux4g-date-picker-grid');
      this.monthLabel = container.querySelector('.ux4g-date-picker-current');

      const navBtns = container.querySelectorAll('.ux4g-date-picker-nav-btn');
      this.prevBtn = navBtns[0];
      this.nextBtn = navBtns[1];

      this.confirmBtn = container.querySelector('.ux4g-btn-primary');
      this.cancelBtn = container.querySelector('.ux4g-btn-outline-neutral');

      this.currentDate = new Date();
      this.viewDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth(), 1);
      this.startDate = null;
      this.endDate = null;
      this.tempStartDate = null;
      this.tempEndDate = null;
      this.selectingEnd = false;
      this.isSelectingYearMonth = false;

      this._init();
    }

    _init() {
      if (!this.inputs.length || !this.dropdown) return;

      this.inputs.forEach(input => {
        // Keyboard accessibility
        input.addEventListener('keydown', (e) => {
          if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            this.open();
          }
        });

        input.addEventListener('focus', (e) => {
          this.open();
        });

        input.addEventListener('click', (e) => {
          e.stopPropagation();
          this.open();
        });
      });

      if (this.prevBtn) {
        makeKeyboardClickable(this.prevBtn);
        this.prevBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          if (this.isSelectingYearMonth) {
            this.changeYearRange(-8);
          } else {
            this.changeMonth(-1);
          }
        });
      }

      if (this.nextBtn) {
        makeKeyboardClickable(this.nextBtn);
        this.nextBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          if (this.isSelectingYearMonth) {
            this.changeYearRange(8);
          } else {
            this.changeMonth(1);
          }
        });
      }

      if (this.monthLabel) {
        makeKeyboardClickable(this.monthLabel);
        this.monthLabel.addEventListener('click', (e) => {
          e.stopPropagation();
          this.isSelectingYearMonth = !this.isSelectingYearMonth;
          this.render();
          setTimeout(() => {
            if (this.isSelectingYearMonth) {
              const calendarContainer = this.container.querySelector('.ux4g-date-picker-calendar');
              let el = calendarContainer.querySelector('.ux4g-date-picker-year-item.is-selected') || calendarContainer.querySelector('.ux4g-date-picker-year-item');
              if (el) el.focus();
            } else {
              let el = this.calendarGrid.querySelector('.is-range-start') || this.calendarGrid.querySelector('.is-selected') || this.calendarGrid.querySelector('.is-today') || this.calendarGrid.querySelector('.ux4g-date-picker-day:not(.is-muted)');
              if (el) el.focus();
            }
          }, 0);
        });
      }

      if (this.confirmBtn) {
        makeKeyboardClickable(this.confirmBtn);
        this.confirmBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          if (this.isSelectingYearMonth) {
            this.isSelectingYearMonth = false;
            this.render();
            setTimeout(() => {
              let el = this.calendarGrid.querySelector('.is-range-start') || this.calendarGrid.querySelector('.is-selected') || this.calendarGrid.querySelector('.is-today') || this.calendarGrid.querySelector('.ux4g-date-picker-day:not(.is-muted)');
              if (el) el.focus();
            }, 0);
          } else {
            this.confirmSelection();
          }
        });
      }

      if (this.cancelBtn) {
        this.cancelBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          this.cancelSelection();
        });
      }

      document.addEventListener('click', (e) => {
        if (!this.container.contains(e.target) && !getBackdrop().contains(e.target)) {
          this.close();
        }
      });

      getBackdrop().addEventListener('click', () => {
        this.close();
      });

      this.render();
    }

    open() {
      if (this.dropdown) {
        this.tempStartDate = this.startDate ? new Date(this.startDate) : null;
        this.tempEndDate = this.endDate ? new Date(this.endDate) : null;
        this.selectingEnd = this.tempStartDate && !this.tempEndDate;
        this.isSelectingYearMonth = false;
        this.dropdown.classList.add('is-open');
        if (isMobile()) {
          getBackdrop().classList.add('is-active');
          document.body.style.overflow = 'hidden';
        } else {
          adjustDropdownPosition(this.dropdown, this.container);
        }
        this.render();
      }
    }

    close() {
      if (this.dropdown) {
        this.dropdown.classList.remove('is-open');
        resetDropdownPosition(this.dropdown);
        getBackdrop().classList.remove('is-active');
        document.body.style.overflow = '';
      }
    }

    confirmSelection() {
      this.startDate = this.tempStartDate ? new Date(this.tempStartDate) : null;
      this.endDate = this.tempEndDate ? new Date(this.tempEndDate) : null;
      this.updateInputs();
      this.close();
    }

    cancelSelection() {
      this.tempStartDate = this.startDate ? new Date(this.tempStartDate) : null;
      this.tempEndDate = this.endDate ? new Date(this.endDate) : null;
      this.close();
    }

    changeMonth(delta) {
      this.viewDate.setMonth(this.viewDate.getMonth() + delta);
      this.render();
    }

    changeYearRange(delta) {
      this.viewDate.setFullYear(this.viewDate.getFullYear() + delta);
      this.render();
    }

    render() {
      if (this.isSelectingYearMonth) {
        this.renderYearMonthSelection();
      } else {
        this.renderCalendar();
      }
    }

    renderCalendar() {
      const year = this.viewDate.getFullYear();
      const month = this.viewDate.getMonth();
      const monthNames = ["January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December"
      ];

      if (this.monthLabel) {
        this.monthLabel.innerHTML = `${monthNames[month]} ${year} <span class="ux4g-icon-outlined ux4g-fs-18">keyboard_arrow_down</span>`;
      }

      const calendarHtml = `
                <div class="ux4g-date-picker-weekdays">
                    <div class="ux4g-date-picker-weekday">Mo</div>
                    <div class="ux4g-date-picker-weekday">Tu</div>
                    <div class="ux4g-date-picker-weekday">We</div>
                    <div class="ux4g-date-picker-weekday">Th</div>
                    <div class="ux4g-date-picker-weekday">Fr</div>
                    <div class="ux4g-date-picker-weekday">Sa</div>
                    <div class="ux4g-date-picker-weekday">Su</div>
                </div>
                <div class="ux4g-date-picker-grid"></div>
            `;

      const calendarContainer = this.container.querySelector('.ux4g-date-picker-calendar');
      calendarContainer.innerHTML = calendarHtml;
      this.calendarGrid = calendarContainer.querySelector('.ux4g-date-picker-grid');

      const firstDayOfMonth = new Date(year, month, 1).getDay();
      const daysInMonth = new Date(year, month + 1, 0).getDate();
      let startDay = firstDayOfMonth === 0 ? 6 : firstDayOfMonth - 1;
      const prevMonthLastDay = new Date(year, month, 0).getDate();

      let html = '';
      for (let i = startDay - 1; i >= 0; i--) {
        html += `<div class="ux4g-date-picker-day is-muted">${prevMonthLastDay - i}</div>`;
      }

      for (let i = 1; i <= daysInMonth; i++) {
        const date = new Date(year, month, i);
        const isToday = date.toDateString() === this.currentDate.toDateString();

        let classes = 'ux4g-date-picker-day';
        if (isToday) classes += ' is-today';

        if (this.tempStartDate && date.toDateString() === this.tempStartDate.toDateString()) {
          classes += ' is-selected is-range-start';
        } else if (this.tempEndDate && date.toDateString() === this.tempEndDate.toDateString()) {
          classes += ' is-selected is-range-end';
        } else if (this.tempStartDate && this.tempEndDate && date > this.tempStartDate && date < this.tempEndDate) {
          classes += ' is-in-range';
        }

        html += `<div class="${classes}" data-date="${i}" tabindex="0">${i}</div>`;
      }

      const totalCells = 42;
      const remainingCells = totalCells - (startDay + daysInMonth);
      for (let i = 1; i <= remainingCells; i++) {
        html += `<div class="ux4g-date-picker-day is-muted">${i}</div>`;
      }

      this.calendarGrid.innerHTML = html;
      this.calendarGrid.querySelectorAll('.ux4g-date-picker-day:not(.is-muted)').forEach(dayEl => {
        makeKeyboardClickable(dayEl);
        dayEl.addEventListener('click', (e) => {
          e.stopPropagation();
          const day = e.target.dataset.date;
          this.handleDateSelection(new Date(year, month, day));
        });
      });

      if (this.confirmBtn) {
        this.confirmBtn.innerHTML = 'Confirm';
        this.confirmBtn.disabled = !this.tempStartDate || !this.tempEndDate;
      }
    }

    renderYearMonthSelection() {
      const currentYear = this.viewDate.getFullYear();
      const startYear = Math.floor(currentYear / 8) * 8;
      const endYear = startYear + 7;

      if (this.monthLabel) {
        this.monthLabel.innerHTML = `${startYear}-${endYear} <span class="ux4g-icon-outlined ux4g-fs-18">keyboard_arrow_down</span>`;
      }

      let html = '<div class="ux4g-date-picker-selection-view">';
      html += '<div class="ux4g-date-picker-year-grid">';
      for (let y = startYear; y <= endYear; y++) {
        const isSelected = y === this.viewDate.getFullYear();
        html += `<div class="ux4g-date-picker-year-item ${isSelected ? 'is-selected' : ''}" data-year="${y}" tabindex="0">${y}</div>`;
      }
      html += '</div>';

      const monthNamesShort = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
      html += '<div class="ux4g-date-picker-month-grid">';
      monthNamesShort.forEach((m, i) => {
        const isSelected = i === this.viewDate.getMonth();
        html += `<div class="ux4g-date-picker-month-item ${isSelected ? 'is-selected' : ''}" data-month="${i}" tabindex="0">${m}</div>`;
      });
      html += '</div></div>';

      const calendarContainer = this.container.querySelector('.ux4g-date-picker-calendar');
      calendarContainer.innerHTML = html;

      calendarContainer.querySelectorAll('.ux4g-date-picker-year-item').forEach(el => {
        makeKeyboardClickable(el);
        el.addEventListener('click', (e) => {
          e.stopPropagation();
          this.viewDate.setFullYear(parseInt(e.target.dataset.year));
          this.renderYearMonthSelection();
          setTimeout(() => {
            const selectedMonth = this.container.querySelector('.ux4g-date-picker-month-item.is-selected');
            if (selectedMonth) selectedMonth.focus();
          }, 0);
        });
      });

      calendarContainer.querySelectorAll('.ux4g-date-picker-month-item').forEach(el => {
        makeKeyboardClickable(el);
        el.addEventListener('click', (e) => {
          e.stopPropagation();
          this.viewDate.setMonth(parseInt(e.target.dataset.month));
          this.renderYearMonthSelection();
          if (this.confirmBtn) setTimeout(() => this.confirmBtn.focus(), 0);
        });
      });

      if (this.confirmBtn) {
        this.confirmBtn.innerHTML = 'Select date';
        this.confirmBtn.disabled = false;
      }
    }

    handleDateSelection(date) {
      if (!this.tempStartDate || (this.tempStartDate && this.tempEndDate)) {
        this.tempStartDate = date;
        this.tempEndDate = null;
        this.selectingEnd = true;
      } else if (this.selectingEnd) {
        if (date < this.tempStartDate) {
          this.tempEndDate = this.tempStartDate;
          this.tempStartDate = date;
        } else {
          this.tempEndDate = date;
        }
        this.selectingEnd = false;
      }
      this.render();
      if (!this.selectingEnd && this.confirmBtn && !this.confirmBtn.disabled) {
        setTimeout(() => this.confirmBtn.focus(), 0);
      } else if (this.selectingEnd) {
        setTimeout(() => {
          const selectedEl = this.calendarGrid.querySelector('.is-range-start');
          if (selectedEl) selectedEl.focus();
        }, 0);
      }
    }

    updateInputs() {
      if (this.startDate) {
        const d = String(this.startDate.getDate()).padStart(2, '0');
        const m = String(this.startDate.getMonth() + 1).padStart(2, '0');
        const y = this.startDate.getFullYear();
        this.inputs[0].value = `${d}/${m}/${y}`;
      } else {
        this.inputs[0].value = '';
      }
      if (this.endDate) {
        const d = String(this.endDate.getDate()).padStart(2, '0');
        const m = String(this.endDate.getMonth() + 1).padStart(2, '0');
        const y = this.endDate.getFullYear();
        this.inputs[1].value = `${d}/${m}/${y}`;
      } else {
        this.inputs[1].value = '';
      }
    }
  }

  class TimePicker {
    constructor(container) {
      this.container = container;
      this.input = container.querySelector('.ux4g-time-picker-input');
      this.dropdown = container.querySelector('.ux4g-time-picker-dropdown');
      this.hhColumn = container.querySelector('[data-column="hh"]');
      this.mmColumn = container.querySelector('[data-column="mm"]');
      this.ampmBtns = container.querySelectorAll('.ux4g-time-picker-ampm-btn');
      this.confirmBtn = container.querySelector('.ux4g-btn-primary');
      this.cancelBtn = container.querySelector('.ux4g-btn-outline-neutral');

      this.selectedHH = null;
      this.selectedMM = null;
      this.selectedAMPM = "PM";

      this.tempHH = null;
      this.tempMM = null;

      this._init();
    }

    _init() {
      if (!this.input || !this.dropdown) return;

      // Keyboard accessibility
      this.input.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          this.open();
        }
      });

      this.input.addEventListener('focus', (e) => {
        this.open();
      });

      this.input.addEventListener('click', (e) => {
        e.stopPropagation();
        this.open();
      });

      this.ampmBtns.forEach(btn => {
        makeKeyboardClickable(btn);
        btn.addEventListener('click', (e) => {
          e.stopPropagation();
          this.selectedAMPM = e.target.dataset.value;
          this.updateAMPMUI();
        });
      });

      if (this.confirmBtn) {
        makeKeyboardClickable(this.confirmBtn);
        this.confirmBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          if (!this.confirmBtn.disabled) {
            this.confirmSelection();
          }
        });
      }

      if (this.cancelBtn) {
        makeKeyboardClickable(this.cancelBtn);
        this.cancelBtn.addEventListener('click', (e) => {
          e.stopPropagation();
          this.close();
        });
      }

      document.addEventListener('click', (e) => {
        if (!this.container.contains(e.target) && !getBackdrop().contains(e.target)) {
          this.close();
        }
      });

      this.renderColumns();
      this.updateAMPMUI();
      this.validate();
    }

    open() {
      this.tempHH = this.selectedHH;
      this.tempMM = this.selectedMM;

      this.dropdown.classList.add('is-open');
      if (isMobile()) {
        getBackdrop().classList.add('is-active');
        document.body.style.overflow = 'hidden';
      } else {
        adjustDropdownPosition(this.dropdown, this.container);
      }

      this.renderColumns();
      this.scrollToSelected();
      this.validate();
    }

    close() {
      this.dropdown.classList.remove('is-open');
      resetDropdownPosition(this.dropdown);
      getBackdrop().classList.remove('is-active');
      document.body.style.overflow = '';
    }

    validate() {
      if (this.confirmBtn) {
        this.confirmBtn.disabled = !(this.tempHH && this.tempMM);
      }
    }

    confirmSelection() {
      this.selectedHH = this.tempHH;
      this.selectedMM = this.tempMM;
      this.input.value = `${this.selectedHH} : ${this.selectedMM} ${this.selectedAMPM}`;
      this.close();
    }

    updateAMPMUI() {
      this.ampmBtns.forEach(btn => {
        btn.classList.toggle('is-active', btn.dataset.value === this.selectedAMPM);
      });
    }

    renderColumns() {
      // Hours (1-12)
      let hhHtml = '<div class="ux4g-time-picker-col-header">HH</div>';
      for (let i = 1; i <= 12; i++) {
        const val = String(i).padStart(2, '0');
        const isSelected = val === this.tempHH;
        hhHtml += `<div class="ux4g-time-picker-item ${isSelected ? 'is-selected' : ''}" data-value="${val}" tabindex="0">${val}</div>`;
      }
      this.hhColumn.innerHTML = hhHtml;

      // Minutes (0-55, step 5)
      let mmHtml = '<div class="ux4g-time-picker-col-header">MM</div>';
      for (let i = 0; i < 60; i += 5) {
        const val = String(i).padStart(2, '0');
        const isSelected = val === this.tempMM;
        mmHtml += `<div class="ux4g-time-picker-item ${isSelected ? 'is-selected' : ''}" data-value="${val}" tabindex="0">${val}</div>`;
      }
      this.mmColumn.innerHTML = mmHtml;

      // Click events
      this.hhColumn.querySelectorAll('.ux4g-time-picker-item').forEach(el => {
        makeKeyboardClickable(el);
        el.addEventListener('click', (e) => {
          e.stopPropagation();
          this.tempHH = e.target.dataset.value;
          this.updateColumnSelection(this.hhColumn, this.tempHH);
          this.validate();
          if (!this.confirmBtn.disabled) {
            setTimeout(() => this.confirmBtn.focus(), 0);
          } else if (!this.tempMM) {
            const firstMM = this.mmColumn.querySelector('.ux4g-time-picker-item');
            if (firstMM) setTimeout(() => firstMM.focus(), 0);
          }
        });
      });

      this.mmColumn.querySelectorAll('.ux4g-time-picker-item').forEach(el => {
        makeKeyboardClickable(el);
        el.addEventListener('click', (e) => {
          e.stopPropagation();
          this.tempMM = e.target.dataset.value;
          this.updateColumnSelection(this.mmColumn, this.tempMM);
          this.validate();
          if (!this.confirmBtn.disabled) {
            setTimeout(() => this.confirmBtn.focus(), 0);
          }
        });
      });
    }

    updateColumnSelection(column, value) {
      column.querySelectorAll('.ux4g-time-picker-item').forEach(el => {
        el.classList.toggle('is-selected', el.dataset.value === value);
      });
    }

    scrollToSelected() {
      const columns = [this.hhColumn, this.mmColumn];
      columns.forEach(col => {
        const selected = col.querySelector('.is-selected');
        if (selected) {
          col.scrollTop = selected.offsetTop - col.offsetTop - 80;
        }
      });
    }
  }

  const init = () => {
    document.querySelectorAll('.ux4g-date-picker-container').forEach(container => {
      if (!container.closest('.ux4g-date-range-picker') && !initializedDatePickers.has(container)) {
        initializedDatePickers.add(container);
        new DatePicker(container);
      }
    });
    document.querySelectorAll('.ux4g-date-range-picker').forEach(container => {
      if (!initializedRangeDatePickers.has(container)) {
        initializedRangeDatePickers.add(container);
        new RangeDatePicker(container);
      }
    });
    document.querySelectorAll('.ux4g-time-picker-container').forEach(container => {
      if (!initializedTimePickers.has(container)) {
        initializedTimePickers.add(container);
        new TimePicker(container);
      }
    });
  };

  window.ux4gCustomInitList = window.ux4gCustomInitList || [];
  window.ux4gCustomInitList.push(init);

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();

  global.DatePicker = DatePicker;
  global.RangeDatePicker = RangeDatePicker;
  global.TimePicker = TimePicker;
  global.ux4gDateTimePickerInit = init;

})(window);


/********************************* Time Slot JS ***********************************/

class TimeSlotCalendar {
    constructor(container) {
        this.container = container;
        this.calendarGrid = container.querySelector('.ux4g-time-slot-compact-grid');
        this.monthLabel = container.querySelector('.ux4g-time-slot-compact-month');
        this.slotTitle = container.querySelector('.ux4g-time-slot-compact-desktop-header');
        this.slotsList = container.querySelector('.ux4g-time-slot-compact-list');
        this.confirmBtn = container.querySelector('.ux4g-btn-primary');
        
        const navBtns = container.querySelectorAll('.ux4g-btn-icon');
        this.prevBtn = navBtns[0];
        this.nextBtn = navBtns[1];

        this.currentDate = new Date();
        this.viewDate = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth(), 1);
        this.selectedDate = new Date(2026, 3, 23); // Default from design: April 23, 2026

        // Mock Data for Statuses
        this.holidays = ['2026-04-09', '2026-04-21'];
        this.weeklyOffs = [0, 6]; // Sunday, Saturday
        this.noSlotsDates = ['2026-04-08', '2026-04-13'];

        this._init();
    }

    _init() {
        if (this.prevBtn) {
            this.prevBtn.addEventListener('click', () => this.changeMonth(-1));
        }
        if (this.nextBtn) {
            this.nextBtn.addEventListener('click', () => this.changeMonth(1));
        }

        // Cancel Button Reset Logic
        const cancelBtn = this.container.querySelector('.ux4g-btn-outline-neutral');
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                this.selectedDate = null; // Reset selection
                this.render(); // Re-render calendar to clear highlights
                if (this.slotTitle) this.slotTitle.innerText = "Select a Date"; // Reset slot header
                this.resetSlots(); // Clear slot selection and disable confirm button
            });
        }

        this.render();
    }

    changeMonth(delta) {
        this.viewDate.setMonth(this.viewDate.getMonth() + delta);
        this.render();
    }

    render() {
        this.renderCalendar();
    }

    renderCalendar() {
        const year = this.viewDate.getFullYear();
        const month = this.viewDate.getMonth();
        const monthNames = ["January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"
        ];

        // Update Month Label
        if (this.monthLabel) {
            this.monthLabel.innerText = `${monthNames[month]} ${year}`;
        }

        const firstDayOfMonth = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        
        // Adjust for Monday start
        let startDay = firstDayOfMonth === 0 ? 6 : firstDayOfMonth - 1;
        const prevMonthLastDay = new Date(year, month, 0).getDate();

        // Clear and rebuild grid
        this.calendarGrid.innerHTML = '';

        // Add Weekday Names
        const days = ["Mo", "Tu", "We", "Th", "Fr", "Sa", "Su"];
        days.forEach(day => {
            const dayEl = document.createElement('div');
            dayEl.className = 'ux4g-time-slot-day-name';
            dayEl.innerText = day;
            this.calendarGrid.appendChild(dayEl);
        });

        // Previous Month Days
        for (let i = startDay - 1; i >= 0; i--) {
            const dateEl = document.createElement('div');
            dateEl.className = 'ux4g-time-slot-date muted';
            dateEl.innerText = prevMonthLastDay - i;
            this.calendarGrid.appendChild(dateEl);
        }

        // Current Month Days
        const todayMidnight = new Date(this.currentDate.getFullYear(), this.currentDate.getMonth(), this.currentDate.getDate());
        for (let i = 1; i <= daysInMonth; i++) {
            const date = new Date(year, month, i);
            const dateStr = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(i).padStart(2, '0');
            const isToday = date.toDateString() === this.currentDate.toDateString();
            const isPast = date < todayMidnight;
            const isSelected = this.selectedDate && date.toDateString() === this.selectedDate.toDateString();
            const isHoliday = this.holidays.includes(dateStr);
            const isWeeklyOff = this.weeklyOffs.includes(date.getDay());
            const isNoSlots = this.noSlotsDates.includes(dateStr);

            const dateEl = document.createElement('div');
            dateEl.className = 'ux4g-time-slot-date';
            if (isPast) dateEl.classList.add('disabled');
            if (isToday) dateEl.classList.add('today');
            if (isSelected && !isPast) dateEl.classList.add('selected');
            if (isHoliday) dateEl.classList.add('holiday');
            if (isWeeklyOff) dateEl.classList.add('weekly-off');
            if (isNoSlots) dateEl.classList.add('no-slots');
            
            dateEl.innerText = i;
            dateEl.dataset.date = i;

            if (!isPast) {
                dateEl.addEventListener('click', () => {
                    this.selectedDate = new Date(year, month, i);
                    this.render();
                    this.updateSlotHeader();
                    this.resetSlots();
                });
            }

            this.calendarGrid.appendChild(dateEl);
        }

        // Next Month Days
        const totalCells = 42 + 7; // Including header row
        const currentCells = this.calendarGrid.children.length;
        const remainingCells = totalCells - currentCells;
        for (let i = 1; i <= remainingCells; i++) {
            const dateEl = document.createElement('div');
            dateEl.className = 'ux4g-time-slot-date muted';
            dateEl.innerText = i;
            this.calendarGrid.appendChild(dateEl);
        }
    }

    updateSlotHeader() {
        if (this.slotTitle && this.selectedDate) {
            const day = this.selectedDate.getDate();
            const monthNames = ["January", "February", "March", "April", "May", "June",
                "July", "August", "September", "October", "November", "December"
            ];
            const month = monthNames[this.selectedDate.getMonth()];
            const year = this.selectedDate.getFullYear();
            this.slotTitle.innerText = `${day}${this.getOrdinal(day)} ${month} ${year}`;
        }
    }

    resetSlots() {
        // Reset slot selection and disable confirm button
        const slots = this.container.querySelectorAll('.ux4g-time-slot-compact-slot-item:not(.disabled)');
        slots.forEach(s => {
            s.classList.remove('selected');
            s.style.backgroundColor = '';
        });
        if (this.confirmBtn) this.confirmBtn.setAttribute('disabled', 'true');
        
        // Re-attach slot selection listeners
        slots.forEach(slot => {
            slot.addEventListener('click', () => {
                slots.forEach(s => {
                    s.classList.remove('selected');
                    s.style.backgroundColor = '';
                });
                slot.classList.add('selected');
                if (this.confirmBtn) this.confirmBtn.removeAttribute('disabled');
            });
        });
    }

    getOrdinal(n) {
        const s = ["th", "st", "nd", "rd"];
        const v = n % 100;
        return (s[(v - 20) % 10] || s[v] || s[0]);
    }
}

// Initialize on Load
document.addEventListener('DOMContentLoaded', () => {
    // Initialize Compact Calendar
    const compactContainers = document.querySelectorAll('.ux4g-time-slot-compact-container, #ux4g-time-slot, [id^="ux4g-time-slot-compact"]');
    compactContainers.forEach(container => {
        new TimeSlotCalendar(container);
    });

    // Initialize Weekly Grid Selection
    const weeklyGrids = document.querySelectorAll('.ux4g-time-slot-weekly-grid, [id^="ux4g-time-slot-weekly"]');
    weeklyGrids.forEach(weeklyGrid => {
        const cells = weeklyGrid.querySelectorAll('.ux4g-time-slot-cell.available, .ux4g-time-slot-cell.limited');
        const confirmBtn = weeklyGrid.parentElement.querySelector('.ux4g-time-slot-weekly-actions .ux4g-btn-primary');
        const cancelBtn = weeklyGrid.parentElement.querySelector('.ux4g-time-slot-weekly-actions .ux4g-btn-outline-neutral');

        cells.forEach(cell => {
            // Store original content to restore later
            const originalHTML = cell.innerHTML;

            cell.addEventListener('click', () => {
                if (cell.classList.contains('selected')) return;

                // 1. Restore all other cells to their original state
                cells.forEach(c => {
                    if (c.classList.contains('selected')) {
                        c.classList.remove('selected');
                        if (c._originalContent) {
                            c.innerHTML = c._originalContent;
                        }
                    }
                });

                // 2. Select this cell
                cell.classList.add('selected');
                cell._originalContent = originalHTML;
                cell.innerHTML = `<span class="ux4g-icon-filled">check_circle</span> Selected`;

                // 3. Enable Confirm Button
                if (confirmBtn) confirmBtn.removeAttribute('disabled');
            });
        });

        // Cancel Button logic for Weekly Grid
        if (cancelBtn) {
            cancelBtn.addEventListener('click', () => {
                cells.forEach(c => {
                    if (c.classList.contains('selected')) {
                        c.classList.remove('selected');
                        if (c._originalContent) {
                            c.innerHTML = c._originalContent;
                        }
                    }
                });
                if (confirmBtn) confirmBtn.setAttribute('disabled', 'true');
            });
        }

        // Initialize Weekly Grid Mobile Navigation (Dynamic approach)
        const container = weeklyGrid.closest('.ux4g-time-slot-weekly-container');
        if (container) {
            const mobileNav = container.querySelector('.ux4g-time-slot-mobile-nav');
            if (mobileNav) {
                const mobileDateLabel = mobileNav.querySelector('.ux4g-time-slot-mobile-date');
                const navBtns = mobileNav.querySelectorAll('.ux4g-btn-icon');
                const prevBtn = navBtns[0];
                const nextBtn = navBtns[1];

                // 1. Dynamically assign data-day attributes to cells based on grid position
                // Grid has 8 columns: Time, Mon, Tue, Wed, Thu, Fri, Sat, Sun
                const children = weeklyGrid.children;
                for (let i = 0; i < children.length; i++) {
                    const colIndex = i % 8;
                    if (colIndex > 0) { // Skip Time column
                        children[i].setAttribute('data-day', colIndex - 1);
                    }
                }

                // 2. Navigation Logic
                let activeDay = 0;
                weeklyGrid.setAttribute('data-active-day', activeDay);

                const daysData = [
                    { day: "Mon 14 Apr", status: "Today" },
                    { day: "Tue 15 Apr", status: "" },
                    { day: "Wed 16 Apr", status: "" },
                    { day: "Thu 17 Apr", status: "Public Holiday" },
                    { day: "Fri 18 Apr", status: "" },
                    { day: "Sat 19 Apr", status: "Weekly off" },
                    { day: "Sun 20 Apr", status: "Weekly off" }
                ];

                const updateMobileNav = (index) => {
                    weeklyGrid.setAttribute('data-active-day', index);
                    const data = daysData[index];
                    if (mobileDateLabel) {
                        mobileDateLabel.innerHTML = `
                            <strong>${data.day}</strong>
                            ${data.status ? `<span class="${data.status === 'Today' ? 'ux4g-text-success-600' : 'ux4g-text-neutral-secondary'}">${data.status}</span>` : ''}
                        `;
                    }
                };

                if (prevBtn) {
                    prevBtn.addEventListener('click', () => {
                        activeDay = (activeDay > 0) ? activeDay - 1 : 6;
                        updateMobileNav(activeDay);
                    });
                }

                if (nextBtn) {
                    nextBtn.addEventListener('click', () => {
                        activeDay = (activeDay < 6) ? activeDay + 1 : 0;
                        updateMobileNav(activeDay);
                    });
                }
            }
        }
    });
});



/********************************* Result list JS ***********************************/

// Accordion Toggle
window.ux4gCustomInitList = window.ux4gCustomInitList || []; window.ux4gCustomInitList.push(() => {
  if (window.ux4gResultListAccordionInitialized) return;
  window.ux4gResultListAccordionInitialized = true;
  
  document.addEventListener('click', (e) => {
    const toggle = e.target.closest('.ux4g-result-list-accordion-toggle');
    if (!toggle) return;

    let content = null;
    
    // 1. Support ID-based target via aria-controls or data-ux-target
    const targetId = toggle.getAttribute('aria-controls') || toggle.getAttribute('data-ux-target');
    if (targetId) {
      const selector = targetId.startsWith('#') ? targetId : `#${targetId}`;
      content = document.querySelector(selector);
    }
    
    // 2. Fallback to structural targeting
    if (!content) {
      const card = toggle.closest('.ux4g-result-list');
      if (card) {
        content = card.querySelector('.ux4g-result-list-content');
      }
    }

    if (!content) return;

    const isExpanded = toggle.getAttribute('aria-expanded') === 'true';

    if (isExpanded) {
      toggle.setAttribute('aria-expanded', 'false');
      toggle.innerText = 'expand_more';
      content.style.display = 'none';
    } else {
      toggle.setAttribute('aria-expanded', 'true');
      toggle.innerText = 'expand_less';
      content.style.display = '';
    }
  });
});



/********************************* Mega menu category list JS ***********************************/

window.ux4gCustomInitList = window.ux4gCustomInitList || []; window.ux4gCustomInitList.push(() => {
  const categoryItems = document.querySelectorAll('.ux4g-mega-menu-category-item');
  const contentBlocks = document.querySelectorAll('.ux4g-mega-menu-content');

  if (!categoryItems.length || !contentBlocks.length) return;

  categoryItems.forEach((item, index) => {
    item.addEventListener('click', (e) => {
      e.preventDefault();

      // Remove active class from all categories
      categoryItems.forEach(cat => cat.classList.remove('ux4g-mega-menu-category-item--active'));
      // Add active class to clicked category
      item.classList.add('ux4g-mega-menu-category-item--active');

      // Hide all content blocks
      contentBlocks.forEach(block => block.classList.remove('ux4g-mega-menu-content--active'));

      // Show the corresponding content block by ID matching category-1, category-2, etc.
      const targetId = `category-${index + 1}`;
      const targetBlock = document.getElementById(targetId);

      if (targetBlock) {
        targetBlock.classList.add('ux4g-mega-menu-content--active');
      }
    });
  });
});

/* ========================================================= checkbox js ========================================================= */
window.ux4gCustomInitList = window.ux4gCustomInitList || []; window.ux4gCustomInitList.push(() => {

  // 1. Standalone indeterminate checkboxes — add data-ux4g-indeterminate attribute to set on init
  document.querySelectorAll('.ux4g-checkbox-input[data-ux4g-indeterminate]').forEach(input => {
    input.indeterminate = true;
  });

  // 2. Parent-child checkbox groups — wrap the group in data-ux4g-parent-child.
  //    Mark the select-all input with data-ux4g-select-all.
  //    Mark each child input with data-ux4g-child.
  document.querySelectorAll('[data-ux4g-parent-child]').forEach(container => {
    const parentInput = container.querySelector('.ux4g-checkbox-input[data-ux4g-select-all]');
    const childInputs = Array.from(container.querySelectorAll('.ux4g-checkbox-input[data-ux4g-child]'));

    if (!parentInput || !childInputs.length) return;

    function updateParent() {
      const checkedCount = childInputs.filter(cb => cb.checked).length;
      if (checkedCount === 0) {
        parentInput.checked = false;
        parentInput.indeterminate = false;
      } else if (checkedCount === childInputs.length) {
        parentInput.checked = true;
        parentInput.indeterminate = false;
      } else {
        parentInput.checked = false;
        parentInput.indeterminate = true;
      }
    }

    // Use click (not change) so we can read the children's state BEFORE any changes,
    // giving consistent "select all / unselect all" behaviour regardless of browser
    // handling of indeterminate → click → checked transitions.
    parentInput.addEventListener('click', function () {
      const checkedCount = childInputs.filter(cb => cb.checked).length;
      const checkAll = checkedCount < childInputs.length;
      parentInput.checked = checkAll;
      parentInput.indeterminate = false;
      childInputs.forEach(cb => { cb.checked = checkAll; });
    });

    childInputs.forEach(cb => cb.addEventListener('change', updateParent));
    updateParent();
  });
});




/* ========================================================= 
   Aadhaar & PAN Validation Logic
========================================================= */
window.ux4gCustomInitList = window.ux4gCustomInitList || [];
window.ux4gCustomInitList.push(() => {
    
    // Helper function to toggle error state
    const toggleErrorState = (input, isError) => {
        const container = input.closest('.ux4g-input-container');
        if (!container) return;
        
        let helper = container.querySelector('.ux4g-input-helper');

        if (isError) {
            container.classList.add('ux4g-input-error');
            input.setAttribute('aria-invalid', 'true');
            if (helper) helper.style.display = 'flex';
        } else {
            container.classList.remove('ux4g-input-error');
            input.removeAttribute('aria-invalid');
            if (helper) helper.style.display = 'none';
        }
    };

    // ---------------------------------------------------------
    // Aadhaar Card Input Handling
    // ---------------------------------------------------------
    const handleAadhaar = (e) => {
        const input = e.target;
        if (!input.matches('input[id="aadhaarInput"], input[name="aadhaar"]')) return;

        let val = input.value.replace(/[^\d]/g, '').substring(0, 12);
        let formatted = '';
        for (let i = 0; i < val.length; i++) {
            if (i > 0 && i % 4 === 0) formatted += ' ';
            formatted += val[i];
        }
        if (input.value !== formatted) {
            input.value = formatted;
        }

        // Validate using HTML5 pattern attribute
        if (input.value.length === 0) {
            toggleErrorState(input, false);
        } else if (!input.checkValidity()) {
            toggleErrorState(input, true);
        } else {
            toggleErrorState(input, false);
        }
    };

    // ---------------------------------------------------------
    // PAN Card Input Handling
    // ---------------------------------------------------------
    const handlePan = (e) => {
        const input = e.target;
        if (!input.matches('input[id="panInput"], input[name="pan"]')) return;

        let val = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 10);
        if (input.value !== val) {
            input.value = val;
        }

        // Validate using HTML5 pattern attribute
        if (input.value.length === 0) {
            toggleErrorState(input, false);
        } else if (!input.checkValidity()) {
            toggleErrorState(input, true);
        } else {
            toggleErrorState(input, false);
        }
    };

    // Use event delegation to handle dynamically created inputs (e.g. inside Playground)
    document.addEventListener('input', (e) => {
        if (e.target && e.target.tagName === 'INPUT') {
            handleAadhaar(e);
            handlePan(e);
        }
    });

    document.addEventListener('focusout', (e) => {
        if (e.target && e.target.tagName === 'INPUT') {
            handleAadhaar(e);
            handlePan(e);
        }
    });
});

window.ux4gCustomInitList = window.ux4gCustomInitList || []; window.ux4gCustomInitList.push(() => {
    const wrappers = document.querySelectorAll('.ux4g-consent-capture-wrapper');
    
    wrappers.forEach(wrapper => {
        const scrollBox = wrapper.querySelector('.ux4g-declaration-content');
        const hint = wrapper.querySelector('.ux4g-declaration-scroll-hint');
        const checkbox = wrapper.querySelector('.ux4g-checkbox-input');
        const nameInputGroup = wrapper.querySelector('.ux4g-input-container');
        const nameInput = wrapper.querySelector('.ux4g-input-input');
        const submitBtn = wrapper.querySelector('.ux4g-consent-decl-btn-wrap .ux4g-btn-primary');

        if (!checkbox || !scrollBox || !submitBtn) return;

        let hasScrolledToBottom = false;

        function validateForm() {
            const isNameValid = nameInput ? nameInput.value.trim().length > 0 : true;
            const isChecked = checkbox.checked;

            if (hasScrolledToBottom && isNameValid && isChecked) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }

        scrollBox.addEventListener('scroll', function() {
            if (Math.abs(this.scrollHeight - this.clientHeight - this.scrollTop) <= 10) {
                if (hint) {
                    hint.style.opacity = '0';
                    hint.style.pointerEvents = 'none';
                }
                hasScrolledToBottom = true;
                validateForm();
            }
        });

        checkbox.addEventListener('change', function() {
            if (nameInputGroup) {
                if (this.checked) {
                    nameInputGroup.classList.remove('ux4g-d-none');
                } else {
                    nameInputGroup.classList.add('ux4g-d-none');
                    if (nameInput) nameInput.value = '';
                }
            }
            validateForm();
        });
        
        if (nameInput) {
            nameInput.addEventListener('input', validateForm);
        }
        
        // Initial validation
        validateForm();
    });
});

/* ========================================================= alert js ========================================================= */

// Single delegated click handler for alert close (Framework Agnostic)
document.addEventListener('click', (e) => {
  const closeBtn = e.target.closest('.ux4g-alert-close');
  if (closeBtn) {
    const alert = closeBtn.closest('.ux4g-alert, .ux4g-context-alert');
    if (alert) {
      alert.style.display = 'none';
    }
  }
});

// Auto-dismiss logic using MutationObserver (Works perfectly with React/Angular)
function initAutoDismissAlerts() {
  const setAutoDismiss = (alert) => {
    if (alert.dataset && alert.dataset.ux4gDismissTimeout) return; // already set
    const time = parseInt(alert.getAttribute('data-auto-dismiss'), 10);
    if (!isNaN(time) && time > 0) {
      const timerId = setTimeout(() => {
        const reminderParent = alert.closest('.ux4g-reminder-alert');
        if (reminderParent) {
          reminderParent.style.display = 'none';
        }
        alert.style.display = 'none';
      }, time);
      if (alert.dataset) {
        alert.dataset.ux4gDismissTimeout = timerId;
      }
    }
  };

  // 1. Check existing alerts on page
  document.querySelectorAll('[data-auto-dismiss], .ux4g-alert[data-auto-dismiss], .ux4g-context-alert[data-auto-dismiss], .ux4g-reminder-alert[data-auto-dismiss]').forEach(setAutoDismiss);

  // 2. Watch for dynamically added alerts (React, Angular, etc.)
  const observer = new MutationObserver((mutations) => {
    mutations.forEach(mutation => {
      mutation.addedNodes.forEach(node => {
        if (node.nodeType === 1) { // Element node
          if (node.hasAttribute && node.hasAttribute('data-auto-dismiss')) {
            setAutoDismiss(node);
          }
          // Also check children if a container was added
          const innerAlerts = node.querySelectorAll ? node.querySelectorAll('[data-auto-dismiss]') : [];
          innerAlerts.forEach(setAutoDismiss);
        }
      });
    });
  });

  if (document.body) {
    observer.observe(document.body, { childList: true, subtree: true });
  }
}

window.ux4gCustomInitList = window.ux4gCustomInitList || [];
window.ux4gCustomInitList.push(initAutoDismissAlerts);


/* ========================================================= reminder alert close js ========================================================= */
// Delegated event listener for reminder alert close buttons so it works with dynamic frameworks (React, Angular, Next.js)
document.addEventListener('click', (e) => {
  const closeBtn = e.target.closest('.ux4g-reminder-alert .ux4g-alert-close');
  if (closeBtn) {
    const alertBox = closeBtn.closest('.ux4g-reminder-alert');
    if (alertBox) {
      alertBox.style.display = 'none';
    }
        if (isError) {
            container.classList.add('ux4g-input-error');
            input.setAttribute('aria-invalid', 'true');
            if (helper) helper.style.display = 'flex';
        } else {
            container.classList.remove('ux4g-input-error');
            input.removeAttribute('aria-invalid');
            if (helper) helper.style.display = 'none';
        }
    };

    // ---------------------------------------------------------
    // Aadhaar Card Input Handling
    // ---------------------------------------------------------
    const handleAadhaar = (e) => {
        const input = e.target;
        if (!input.matches('input[id="aadhaarInput"], input[name="aadhaar"]')) return;

        let val = input.value.replace(/[^\d]/g, '').substring(0, 12);
        let formatted = '';
        for (let i = 0; i < val.length; i++) {
            if (i > 0 && i % 4 === 0) formatted += ' ';
            formatted += val[i];
        }
        if (input.value !== formatted) {
            input.value = formatted;
        }

        // Validate using HTML5 pattern attribute
        if (input.value.length === 0) {
            toggleErrorState(input, false);
        } else if (!input.checkValidity()) {
            toggleErrorState(input, true);
        } else {
            toggleErrorState(input, false);
        }
    };

    // ---------------------------------------------------------
    // PAN Card Input Handling
    // ---------------------------------------------------------
    const handlePan = (e) => {
        const input = e.target;
        if (!input.matches('input[id="panInput"], input[name="pan"]')) return;

        let val = input.value.toUpperCase().replace(/[^A-Z0-9]/g, '').substring(0, 10);
        if (input.value !== val) {
            input.value = val;
        }

        // Validate using HTML5 pattern attribute
        if (input.value.length === 0) {
            toggleErrorState(input, false);
        } else if (!input.checkValidity()) {
            toggleErrorState(input, true);
        } else {
            toggleErrorState(input, false);
        }
    };

    // Use event delegation to handle dynamically created inputs (e.g. inside Playground)
    document.addEventListener('input', (e) => {
        if (e.target && e.target.tagName === 'INPUT') {
            handleAadhaar(e);
            handlePan(e);
        }
    });

    document.addEventListener('focusout', (e) => {
        if (e.target && e.target.tagName === 'INPUT') {
            handleAadhaar(e);
            handlePan(e);
        }
    });
});

window.ux4gCustomInitList = window.ux4gCustomInitList || []; window.ux4gCustomInitList.push(() => {
    const wrappers = document.querySelectorAll('.ux4g-consent-capture-wrapper');
    
    wrappers.forEach(wrapper => {
        const scrollBox = wrapper.querySelector('.ux4g-declaration-content');
        const hint = wrapper.querySelector('.ux4g-declaration-scroll-hint');
        const checkbox = wrapper.querySelector('.ux4g-checkbox-input');
        const nameInputGroup = wrapper.querySelector('.ux4g-input-container');
        const nameInput = wrapper.querySelector('.ux4g-input-input');
        const submitBtn = wrapper.querySelector('.ux4g-consent-decl-btn-wrap .ux4g-btn-primary');

        if (!checkbox || !scrollBox || !submitBtn) return;

        let hasScrolledToBottom = false;

        function validateForm() {
            const isNameValid = nameInput ? nameInput.value.trim().length > 0 : true;
            const isChecked = checkbox.checked;

            if (hasScrolledToBottom && isNameValid && isChecked) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }

        scrollBox.addEventListener('scroll', function() {
            if (Math.abs(this.scrollHeight - this.clientHeight - this.scrollTop) <= 10) {
                if (hint) {
                    hint.style.opacity = '0';
                    hint.style.pointerEvents = 'none';
                }
                hasScrolledToBottom = true;
                validateForm();
            }
        });

        checkbox.addEventListener('change', function() {
            if (nameInputGroup) {
                if (this.checked) {
                    nameInputGroup.classList.remove('ux4g-d-none');
                } else {
                    nameInputGroup.classList.add('ux4g-d-none');
                    if (nameInput) nameInput.value = '';
                }
            }
            validateForm();
        });
        
        if (nameInput) {
            nameInput.addEventListener('input', validateForm);
        }
        
        // Initial validation
        validateForm();
    });
});




/* ========================================================= reminder alert close js ========================================================= */
// Delegated event listener for reminder alert close buttons so it works with dynamic frameworks (React, Angular, Next.js)
document.addEventListener('click', (e) => {
  const closeBtn = e.target.closest('.ux4g-reminder-alert .ux4g-alert-close');
  if (closeBtn) {
    const alertBox = closeBtn.closest('.ux4g-reminder-alert');
    if (alertBox) {
      alertBox.style.display = 'none';
    }
  }
});


/* ========================================================= list switch JS appended ========================================================= */
window.ux4gCustomInitList = window.ux4gCustomInitList || []; window.ux4gCustomInitList.push(() => {
  document.addEventListener('click', (e) => {
    const switchContent = e.target.closest('.ux4g-switch-content');
    if (switchContent) {
      e.preventDefault();
      e.stopPropagation();
      return;
    }

    const row = e.target.closest('.ux4g-list-item-row.ux4g-toggles, span.ux4g-list-item-row, .ux4g-list-item-row-fixed');
    if (row && e.target.tagName !== 'INPUT' && !e.target.closest('.ux4g-switch, .ux4g-checkbox, .ux4g-radio')) {
      e.stopPropagation();
    }
  }, true);
});
/* ========================================================= toggle disable JS ========================================================= */
// Generic script to enable/disable target elements based on checkbox state. Works with dynamic HTML, React, Angular, etc.
document.addEventListener('change', function(e) {
  if (e.target && e.target.hasAttribute('data-ux4g-toggle-disable')) {
    const targetSelector = e.target.getAttribute('data-ux4g-toggle-disable');
    if (targetSelector) {
      const targetEls = document.querySelectorAll(targetSelector);
      targetEls.forEach(el => {
        el.disabled = !e.target.checked;
      });
    }
  }
});
/* ========================================================= filter chip JS ========================================================= */
// Generic script to toggle filter chips (active mode)
document.addEventListener('click', (e) => {
  const filterChip = e.target.closest('.ux4g-filter-chip-m, .ux4g-filter-chip-s');
  if (filterChip) {
    if (filterChip.hasAttribute('disabled') || filterChip.classList.contains('ux4g-disabled')) return;

    filterChip.classList.toggle('active');
  }
});

// Execute all registered initialization functions (uses ux4g.js's safe init to prevent double-binding)
// Initialize selection state in custom list dropdowns
const initCustomListDropdowns = () => {
  document.querySelectorAll('.ux4g-custom-list-dropdown').forEach(dropdown => {
    const btn = dropdown.querySelector('.ux4g-custom-dropdown-btn');
    const items = dropdown.querySelectorAll('.ux4g-custom-dropdown-item');
    if (!btn || !items.length) return;

    const currentValue = btn.getAttribute('data-value');
    const span = btn.querySelector('span:first-child');
    const currentText = span ? span.textContent.trim() : '';

    items.forEach(item => {
      const itemVal = item.getAttribute('data-value');
      const textEl = item.querySelector('.ux4g-custom-dropdown-text');
      const itemText = textEl ? textEl.textContent.trim() : item.textContent.trim();
      
      const isSelected = (currentValue && itemVal === currentValue) || (!currentValue && currentText && itemText === currentText);
      if (isSelected) {
        item.classList.add('is-selected', 'active');
        item.setAttribute('aria-selected', 'true');
      } else {
        item.classList.remove('is-selected', 'active');
        item.setAttribute('aria-selected', 'false');
      }
    });
  });
};

// Execute all registered initialization functions
const ux4gInitAll = () => {
  if (typeof window.ux4gCustomInit === 'function') {
    try { window.ux4gCustomInit(); } catch(e) { console.error(e); }
  }
  if (Array.isArray(window.ux4gCustomInitList)) {
    window.ux4gCustomInitList.forEach(fn => {
      if (typeof fn === 'function') {
        try { fn(); } catch(e) { console.error(e); }
      }
    });
  }
};

const ux4gCustomInitAllCombined = () => {
  ux4gInitAll();
  initCustomListDropdowns();
};

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', ux4gCustomInitAllCombined);
} else {
  ux4gCustomInitAllCombined();
}


/* ========================================================= custom list dropdown JS ========================================================= */
document.addEventListener('click', (e) => {
  // 1. Handle toggle button click
  const btn = e.target.closest('.ux4g-custom-dropdown-btn');
  if (btn) {
    e.stopPropagation();
    const dropdown = btn.closest('.ux4g-custom-list-dropdown');
    if (dropdown) {
      const menu = dropdown.querySelector('.ux4g-custom-dropdown-menu');
      if (menu) {
        const isExpanded = btn.getAttribute('aria-expanded') === 'true';
        // Close other custom dropdowns
        document.querySelectorAll('.ux4g-custom-list-dropdown').forEach(d => {
          if (d !== dropdown) {
            const otherBtn = d.querySelector('.ux4g-custom-dropdown-btn');
            const otherMenu = d.querySelector('.ux4g-custom-dropdown-menu');
            if (otherBtn) otherBtn.setAttribute('aria-expanded', 'false');
            if (otherMenu) otherMenu.style.display = 'none';
          }
        });
        
        btn.setAttribute('aria-expanded', String(!isExpanded));
        menu.style.display = isExpanded ? 'none' : 'block';
      }
    }
    return;
  }

  // 2. Handle item selection
  const item = e.target.closest('.ux4g-custom-list-dropdown .ux4g-custom-dropdown-item');
  if (item) {
    e.stopPropagation();
    const dropdown = item.closest('.ux4g-custom-list-dropdown');
    if (dropdown) {
      const btn = dropdown.querySelector('.ux4g-custom-dropdown-btn');
      const menu = dropdown.querySelector('.ux4g-custom-dropdown-menu');
      
      // Toggle selected class and aria-selected attribute
      const items = dropdown.querySelectorAll('.ux4g-custom-dropdown-item');
      items.forEach(el => {
        if (el === item) {
          el.classList.add('is-selected', 'active');
          el.setAttribute('aria-selected', 'true');
        } else {
          el.classList.remove('is-selected', 'active');
          el.setAttribute('aria-selected', 'false');
        }
      });

      const span = btn ? btn.querySelector('span:first-child') : null;
      if (span) {
        const textEl = item.querySelector('.ux4g-custom-dropdown-text');
        span.textContent = textEl ? textEl.textContent.trim() : item.textContent.trim();
      }
      if (btn) {
        btn.setAttribute('aria-expanded', 'false');
        btn.setAttribute('data-value', item.getAttribute('data-value') || '');
      }
      if (menu) menu.style.display = 'none';
    }
    return;
  }

  // 3. Clicked outside - close all custom dropdowns
  document.querySelectorAll('.ux4g-custom-list-dropdown').forEach(d => {
    const otherBtn = d.querySelector('.ux4g-custom-dropdown-btn');
    const otherMenu = d.querySelector('.ux4g-custom-dropdown-menu');
    if (otherBtn) otherBtn.setAttribute('aria-expanded', 'false');
    if (otherMenu) otherMenu.style.display = 'none';
  });
});

/********************************* Mega Menu Five Category list JS ***********************************/
window.ux4gCustomInitList = window.ux4gCustomInitList || []; window.ux4gCustomInitList.push(() => {
  const categoryItems = document.querySelectorAll('.ux4g-mega-menu-five-category-item');
  const contentBlocks = document.querySelectorAll('.ux4g-mega-menu-five-content');

  if (!categoryItems.length || !contentBlocks.length) return;

  categoryItems.forEach((item, index) => {
    item.addEventListener('click', (e) => {
      e.preventDefault();

      // Remove active class from all categories
      categoryItems.forEach(cat => cat.classList.remove('ux4g-mega-menu-five-category-item--active'));
      // Add active class to clicked category
      item.classList.add('ux4g-mega-menu-five-category-item--active');

      // Hide all content blocks
      contentBlocks.forEach(block => block.classList.remove('ux4g-mega-menu-five-content--active'));

      // Show the corresponding content block by ID matching five-category-1, five-category-2, etc.
      const targetId = `five-category-${index + 1}`;
      const targetBlock = document.getElementById(targetId);

      if (targetBlock) {
        targetBlock.classList.add('ux4g-mega-menu-five-content--active');
      }
    });
  });
});

/* ==========================================================================
   UX4G Select Option Component JS
   Custom Select component utilizing <select> and <option> tags.
   Handles Single Select and Multi Select with S, M, L variations without <button> tags.
   ========================================================================== */
(function() {
  /**
   * Initializes or syncs a .ux4g-select element with its underlying <select>
   */
  function initUx4gSelect(container) {
    if (!container) return;
    const nativeSelect = container.querySelector('select.ux4g-select-native');
    const textDisplay = container.querySelector('.ux4g-select-text');
    const isMulti = container.classList.contains('ux4g-select-multi');

    if (!nativeSelect) return;

    nativeSelect.setAttribute('tabindex', '-1');
    nativeSelect.setAttribute('aria-hidden', 'true');

    // Build or sync custom menu from <option> elements if menu not already generated
    let menu = container.querySelector('.ux4g-select-menu');
    if (!menu) {
      menu = document.createElement('div');
      menu.className = 'ux4g-select-menu';
      menu.setAttribute('role', 'listbox');
      const list = document.createElement('div');
      list.className = 'ux4g-select-list';

      Array.from(nativeSelect.options).forEach(function(opt) {
        if (opt.disabled && opt.hidden) return; // skip placeholder option
        if (isMulti) {
          const row = document.createElement('div');
          row.className = 'ux4g-select-checkbox-row' + (opt.selected ? ' is-checked is-selected' : '');
          row.setAttribute('data-value', opt.value);
          row.setAttribute('role', 'checkbox');
          row.setAttribute('aria-checked', opt.selected ? 'true' : 'false');
          row.setAttribute('tabindex', '0');

          const cbContainer = document.createElement('div');
          cbContainer.className = 'ux4g-checkbox ux4g-checkbox-s ux4g-w-auto';

          const cbInput = document.createElement('input');
          cbInput.className = 'ux4g-checkbox-input';
          cbInput.type = 'checkbox';
          cbInput.value = opt.value;
          if (opt.selected) cbInput.checked = true;
          cbInput.setAttribute('tabindex', '-1');
          cbInput.setAttribute('aria-hidden', 'true');

          const cbControl = document.createElement('div');
          cbControl.className = 'ux4g-checkbox-control';
          const checkmark = document.createElement('span');
          checkmark.className = 'ux4g-checkmark';
          cbControl.appendChild(checkmark);

          cbContainer.append(cbInput, cbControl);

          const labelSpan = document.createElement('span');
          labelSpan.className = 'ux4g-select-checkbox-label';
          labelSpan.textContent = opt.text || opt.value;

          row.append(cbContainer, labelSpan);
          list.appendChild(row);
        } else {
          const item = document.createElement('div');
          item.className = 'ux4g-select-option' + (opt.selected && opt.value ? ' is-selected' : '');
          item.setAttribute('data-value', opt.value);
          item.setAttribute('role', 'option');
          item.setAttribute('aria-selected', opt.selected && opt.value ? 'true' : 'false');
          item.setAttribute('tabindex', '0');

          const labelSpan = document.createElement('span');
          labelSpan.className = 'ux4g-select-option-label';
          labelSpan.textContent = opt.text || opt.value;

          const checkIcon = document.createElement('span');
          checkIcon.className = 'ux4g-icon-outlined ux4g-select-option-check';
          checkIcon.setAttribute('aria-hidden', 'true');
          checkIcon.textContent = 'check';

          item.append(labelSpan, checkIcon);
          list.appendChild(item);
        }
      });
      menu.appendChild(list);
      container.appendChild(menu);
    }

    // Set initial display text based on current select value
    if (isMulti) {
      const selectedOpts = Array.from(nativeSelect.selectedOptions).filter(function(o) { return o.value; });
      if (selectedOpts.length > 0 && textDisplay) {
        textDisplay.textContent = selectedOpts.map(function(o) { return o.text; }).join(', ');
        textDisplay.classList.remove('is-placeholder');
      }
    } else {
      if (nativeSelect.value && nativeSelect.selectedIndex >= 0) {
        const selectedOpt = nativeSelect.options[nativeSelect.selectedIndex];
        if (selectedOpt && selectedOpt.value && textDisplay) {
          textDisplay.textContent = selectedOpt.text;
          textDisplay.classList.remove('is-placeholder');
        }
      }
    }
  }

  // Initialize all .ux4g-select instances on page load
  function initAll() {
    document.querySelectorAll('.ux4g-select').forEach(initUx4gSelect);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAll);
  } else {
    initAll();
  }

  // Global click delegate for .ux4g-select
  document.addEventListener('click', function(e) {
    // -------------------------------------------------------------
    // 1. Click on Multi-Select Checkbox Row (Priority check)
    // -------------------------------------------------------------
    const multiRow = e.target.closest('.ux4g-select-multi .ux4g-select-checkbox-row');
    if (multiRow) {
      e.stopPropagation();
      const selectContainer = multiRow.closest('.ux4g-select');
      if (!selectContainer) return;

      const checkbox = multiRow.querySelector('.ux4g-checkbox-input');
      const nativeSelect = selectContainer.querySelector('select.ux4g-select-native');
      const textDisplay = selectContainer.querySelector('.ux4g-select-text');
      const rowVal = multiRow.getAttribute('data-value') || (checkbox ? checkbox.value : '');

      const isCurrentlyChecked = multiRow.classList.contains('is-checked');
      const willBeChecked = !isCurrentlyChecked;

      if (willBeChecked) {
        multiRow.classList.add('is-checked', 'is-selected');
        if (checkbox) checkbox.checked = true;
        multiRow.setAttribute('aria-checked', 'true');
      } else {
        multiRow.classList.remove('is-checked', 'is-selected');
        if (checkbox) checkbox.checked = false;
        multiRow.setAttribute('aria-checked', 'false');
      }

      // Sync with native <select multiple>
      if (nativeSelect) {
        Array.from(nativeSelect.options).forEach(function(opt) {
          if (opt.value === rowVal || opt.text.trim() === rowVal) {
            opt.selected = willBeChecked;
          }
        });
        nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
      }

      // Update text display in the trigger
      const checkedRows = selectContainer.querySelectorAll('.ux4g-select-checkbox-row.is-checked');
      const placeholder = (textDisplay && textDisplay.getAttribute('data-placeholder')) || 'Start typing..';

      if (checkedRows.length === 0) {
        if (textDisplay) {
          textDisplay.textContent = placeholder;
          textDisplay.classList.add('is-placeholder');
        }
      } else {
        const labels = Array.from(checkedRows).map(function(r) {
          const l = r.querySelector('.ux4g-select-checkbox-label');
          return l ? l.textContent.trim() : (r.getAttribute('data-value') || '');
        }).filter(Boolean);

        if (textDisplay) {
          textDisplay.textContent = labels.join(', ');
          textDisplay.classList.remove('is-placeholder');
        }
      }
      return;
    }

    // -------------------------------------------------------------
    // 2. Click on Single Select Option
    // -------------------------------------------------------------
    const singleOption = e.target.closest('.ux4g-select-single .ux4g-select-option');
    if (singleOption) {
      e.stopPropagation();
      const selectContainer = singleOption.closest('.ux4g-select');
      if (!selectContainer) return;

      const nativeSelect = selectContainer.querySelector('select.ux4g-select-native');
      const textDisplay = selectContainer.querySelector('.ux4g-select-text');
      const control = selectContainer.querySelector('.ux4g-select-control');
      const optionLabelEl = singleOption.querySelector('.ux4g-select-option-label');
      const optionVal = singleOption.getAttribute('data-value') || (optionLabelEl ? optionLabelEl.textContent.trim() : singleOption.textContent.trim());
      const optionLabel = optionLabelEl ? optionLabelEl.textContent.trim() : optionVal;

      // Update native select
      if (nativeSelect) {
        nativeSelect.value = optionVal;
        nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
        nativeSelect.dispatchEvent(new Event('input', { bubbles: true }));
      }

      // Update custom option active states
      selectContainer.querySelectorAll('.ux4g-select-option').forEach(function(opt) {
        opt.classList.remove('is-selected');
        opt.setAttribute('aria-selected', 'false');
      });
      singleOption.classList.add('is-selected');
      singleOption.setAttribute('aria-selected', 'true');

      // Update display text
      if (textDisplay) {
        textDisplay.textContent = optionLabel;
        textDisplay.classList.remove('is-placeholder');
      }

      // Close dropdown
      selectContainer.classList.remove('is-open');
      if (control) control.setAttribute('aria-expanded', 'false');
      return;
    }

    // -------------------------------------------------------------
    // 3. Click on Trigger Control (Open/Close)
    // -------------------------------------------------------------
    const control = e.target.closest('.ux4g-select-control');
    if (control) {
      // Do not toggle if clicking inside the menu
      if (e.target.closest('.ux4g-select-menu')) return;
      e.stopPropagation();
      const selectContainer = control.closest('.ux4g-select');
      if (!selectContainer) return;

      const isOpen = selectContainer.classList.contains('is-open');

      // Close all other selects
      document.querySelectorAll('.ux4g-select.is-open').forEach(function(el) {
        if (el !== selectContainer) {
          el.classList.remove('is-open');
          const ctrl = el.querySelector('.ux4g-select-control');
          if (ctrl) ctrl.setAttribute('aria-expanded', 'false');
        }
      });

      if (isOpen) {
        selectContainer.classList.remove('is-open');
        control.setAttribute('aria-expanded', 'false');
      } else {
        selectContainer.classList.add('is-open');
        control.setAttribute('aria-expanded', 'true');
      }
      return;
    }

    // -------------------------------------------------------------
    // 4. Click Outside - Close all open Select components
    // -------------------------------------------------------------
    document.querySelectorAll('.ux4g-select.is-open').forEach(function(el) {
      el.classList.remove('is-open');
      const ctrl = el.querySelector('.ux4g-select-control');
      if (ctrl) ctrl.setAttribute('aria-expanded', 'false');
    });
  });

  // -------------------------------------------------------------
  // 5. Keyboard Navigation & Enter Key Support
  // -------------------------------------------------------------
  document.addEventListener('keydown', function(e) {
    // A. Enter / Space / Arrow keys on .ux4g-select-control (Open / Toggle Dropdown)
    const control = document.activeElement ? document.activeElement.closest('.ux4g-select-control') : null;
    if (control && (e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar' || e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
      e.preventDefault();
      const selectContainer = control.closest('.ux4g-select');
      if (!selectContainer) return;

      const isOpen = selectContainer.classList.contains('is-open');

      // Close all other selects
      document.querySelectorAll('.ux4g-select.is-open').forEach(function(el) {
        if (el !== selectContainer) {
          el.classList.remove('is-open');
          const ctrl = el.querySelector('.ux4g-select-control');
          if (ctrl) ctrl.setAttribute('aria-expanded', 'false');
        }
      });

      if (isOpen && (e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar')) {
        selectContainer.classList.remove('is-open');
        control.setAttribute('aria-expanded', 'false');
      } else {
        selectContainer.classList.add('is-open');
        control.setAttribute('aria-expanded', 'true');

        // Focus first option when opening via keyboard
        const firstOpt = selectContainer.querySelector('.ux4g-select-option, .ux4g-select-checkbox-row');
        if (firstOpt) {
          firstOpt.focus();
        }
      }
      return;
    }

    // B. Enter or Space on an Option (Single Select)
    const singleOption = document.activeElement ? document.activeElement.closest('.ux4g-select-single .ux4g-select-option') : null;
    if (singleOption && (e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar')) {
      e.preventDefault();
      singleOption.click();
      return;
    }

    // C. Enter or Space on a Checkbox Row (Multi Select)
    const multiRow = document.activeElement ? document.activeElement.closest('.ux4g-select-multi .ux4g-select-checkbox-row') : null;
    if (multiRow && (e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar')) {
      e.preventDefault();
      multiRow.click();
      return;
    }

    // D. Arrow Navigation inside Open Menu
    const activeOption = document.activeElement ? document.activeElement.closest('.ux4g-select-option, .ux4g-select-checkbox-row') : null;
    if (activeOption && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
      e.preventDefault();
      const menu = activeOption.closest('.ux4g-select-menu');
      if (menu) {
        const options = Array.from(menu.querySelectorAll('.ux4g-select-option, .ux4g-select-checkbox-row'));
        const index = options.indexOf(activeOption);
        if (e.key === 'ArrowDown' && index < options.length - 1) {
          options[index + 1].focus();
        } else if (e.key === 'ArrowUp' && index > 0) {
          options[index - 1].focus();
        } else if (e.key === 'ArrowUp' && index === 0) {
          const selectContainer = activeOption.closest('.ux4g-select');
          const ctrl = selectContainer ? selectContainer.querySelector('.ux4g-select-control') : null;
          if (ctrl) ctrl.focus();
        }
      }
      return;
    }

    // E. Close on Escape Key
    if (e.key === 'Escape') {
      document.querySelectorAll('.ux4g-select.is-open').forEach(function(el) {
        el.classList.remove('is-open');
        const ctrl = el.querySelector('.ux4g-select-control');
        if (ctrl) {
          ctrl.setAttribute('aria-expanded', 'false');
          ctrl.focus();
        }
      });
    }
  });
})();

/* ========================================================= 
   Consent Capture & Declaration Before Submission Pattern Logic
   (src/pattern-html/consent-and-declaration/consent.html)
   ========================================================= */
window.ux4gCustomInitList = window.ux4gCustomInitList || [];
window.ux4gCustomInitList.push(() => {
  // -------------------------------------------------------------
  // Consent Capture: Dynamic Allow Button & Warning Alert Banner
  // -------------------------------------------------------------
  const consentContainers = document.querySelectorAll('.ux4g-consent-card, .ux4g-consent-full-wrapper');

  consentContainers.forEach(container => {
    const list = container.querySelector('.ux4g-consent-list');
    const allowBtn = container.querySelector('.ux4g-consent-actions .ux4g-btn-primary');
    const alertBanner = container.querySelector('.ux4g-consent-alert');

    if (!list || !allowBtn) return;

    function updateConsentState() {
      const checkedCheckboxes = list.querySelectorAll('.ux4g-checkbox-input:checked');
      const hasConsent = checkedCheckboxes.length > 0;

      if (hasConsent) {
        allowBtn.removeAttribute('disabled');
        allowBtn.disabled = false;
        if (alertBanner) alertBanner.style.display = 'none';
      } else {
        allowBtn.setAttribute('disabled', 'disabled');
        allowBtn.disabled = true;
        if (alertBanner) alertBanner.style.display = 'flex';
      }
    }

    // Checkbox change listener
    container.addEventListener('change', (e) => {
      if (e.target.matches('.ux4g-checkbox-input')) {
        updateConsentState();
      }
    });

    // Click listener for select-all and custom checkbox toggles
    container.addEventListener('click', (e) => {
      if (e.target.closest('.ux4g-checkbox') || e.target.matches('.ux4g-checkbox-input')) {
        setTimeout(updateConsentState, 20);
      }
    });
  });

  // -------------------------------------------------------------
  // Declaration Before Submission Interactive Flow
  // -------------------------------------------------------------
  const scrollboxes = document.querySelectorAll('[data-declaration-scrollbox]');
  scrollboxes.forEach(box => {
    const key = box.getAttribute('data-declaration-scrollbox');
    const prompt = document.querySelector(`[data-scroll-prompt="${key}"]`);
    const agreeCb = document.querySelector(`[data-agree-checkbox="${key}"]`);
    const agreeGroup = document.querySelector(`[data-agree-checkbox-group="${key}"]`);
    const sigBox = document.querySelector(`[data-signature-box="${key}"]`);
    const nameInput = document.querySelector(`[data-name-input="${key}"]`);
    const inputWrap = document.querySelector(`[data-input-wrapper="${key}"]`);
    const helperText = document.querySelector(`[data-helper-text="${key}"]`);
    const submitBtn = document.querySelector(`[data-submit-btn="${key}"]`);

    function checkScroll() {
      if (box.scrollHeight - box.scrollTop <= box.clientHeight + 20) {
        if (prompt) prompt.style.display = 'none';
        if (agreeCb) {
          agreeCb.removeAttribute('disabled');
          agreeCb.disabled = false;
        }
        if (agreeGroup) agreeGroup.classList.remove('ux4g-checkbox-disabled');
      }
    }

    box.addEventListener('scroll', checkScroll);
    if (prompt) {
      prompt.addEventListener('click', () => {
        box.scrollTo({ top: box.scrollHeight, behavior: 'smooth' });
      });
    }

    if (agreeCb) {
      agreeCb.addEventListener('change', () => {
        if (agreeCb.checked) {
          if (sigBox) sigBox.style.display = 'block';
        } else {
          if (sigBox) sigBox.style.display = 'none';
          if (submitBtn) {
            submitBtn.setAttribute('disabled', 'disabled');
            submitBtn.disabled = true;
          }
        }
      });
    }

    if (nameInput) {
      nameInput.addEventListener('input', () => {
        const val = nameInput.value.trim();
        const suffixIcon = inputWrap ? inputWrap.querySelector('.ux4g-icon-success') : null;
        if (val.length > 2) {
          if (inputWrap) inputWrap.classList.add('is-success');
          if (suffixIcon) suffixIcon.style.display = 'inline-flex';
          if (helperText) {
            helperText.className = 'ux4g-input-helper ux4g-body-s-default ux4g-text-success ux4g-mt-xs ux4g-d-flex ux4g-ai-center ux4g-gap-x-2xs';
            helperText.innerHTML = '<span class="ux4g-icon ux4g-fs-16">check_circle</span> Your name will be matched against your aadhaar records.';
          }
          if (submitBtn) {
            submitBtn.removeAttribute('disabled');
            submitBtn.disabled = false;
          }
        } else {
          if (inputWrap) inputWrap.classList.remove('is-success');
          if (suffixIcon) suffixIcon.style.display = 'none';
          if (helperText) {
            helperText.className = 'ux4g-input-helper ux4g-body-s-default ux4g-text-neutral-secondary ux4g-mt-xs ux4g-d-flex ux4g-ai-center ux4g-gap-x-2xs';
            helperText.innerHTML = '<span class="ux4g-icon-outlined ux4g-fs-16">info</span> Your name will be matched against your aadhaar records.';
          }
          if (submitBtn) {
            submitBtn.setAttribute('disabled', 'disabled');
            submitBtn.disabled = true;
          }
        }
      });
    }
  });
});

/* ========================================================= 
   FLOATING ACTION MENU (FAM) COMPONENT CONTROLLER
   Accessible keyboard navigation and interaction handler
   ========================================================= */
(function (window, document) {
  if (!document) return;

  /**
   * Open or close a single Floating Action Menu
   * @param {HTMLElement} menu - The menu element (.ux4g-floating-action-menu)
   * @param {boolean} isOpen - Target state (true to open, false to close)
   * @param {boolean} [focusFirst=false] - Whether to focus the first item
   * @param {boolean} [focusLast=false] - Whether to focus the last item
   */
  function setFamOpen(menu, isOpen, focusFirst, focusLast) {
    if (!menu) return;
    const trigger = menu.querySelector('.ux4g-fam-trigger, .ux4g-floating-action-menu-trigger');
    const itemsContainer = menu.querySelector('.ux4g-fam-items, .ux4g-floating-action-menu-items');
    const items = Array.from(menu.querySelectorAll('.ux4g-fam-item'));

    if (isOpen) {
      menu.classList.add('is-open');
      if (trigger) trigger.setAttribute('aria-expanded', 'true');
      if (itemsContainer) itemsContainer.setAttribute('aria-hidden', 'false');
      items.forEach(item => item.setAttribute('tabindex', '0'));

      setTimeout(() => {
        if (focusFirst && items.length > 0) {
          items[0].focus();
        } else if (focusLast && items.length > 0) {
          items[items.length - 1].focus();
        }
      }, 20);
    } else {
      menu.classList.remove('is-open');
      if (trigger) trigger.setAttribute('aria-expanded', 'false');
      if (itemsContainer) itemsContainer.setAttribute('aria-hidden', 'true');
      items.forEach(item => item.setAttribute('tabindex', '-1'));
    }
  }

  /**
   * Toggle a Floating Action Menu
   * @param {HTMLElement} btn - The trigger button element
   * @param {boolean} [focusFirst] - Focus first item on open
   * @param {boolean} [focusLast] - Focus last item on open
   */
  function toggleFam(btn, focusFirst, focusLast) {
    if (!btn) return;
    const menu = btn.closest('.ux4g-floating-action-menu');
    if (!menu) return;
    const isCurrentlyOpen = menu.classList.contains('is-open');
    setFamOpen(menu, !isCurrentlyOpen, focusFirst, focusLast);
  }

  /**
   * Toggle all Floating Action Menus on page
   */
  let allOpenState = false;
  function toggleAllMenus() {
    allOpenState = !allOpenState;
    document.querySelectorAll('.ux4g-floating-action-menu').forEach(menu => {
      setFamOpen(menu, allOpenState);
    });
  }

  /**
   * Handle click on menu items
   * @param {string} actionName - Name of the action
   */
  function handleItemClick(actionName) {
    console.log(`Action executed: ${actionName}`);
    const activeMenu = document.activeElement ? document.activeElement.closest('.ux4g-floating-action-menu') : null;
    if (activeMenu) {
      const trigger = activeMenu.querySelector('.ux4g-fam-trigger, .ux4g-floating-action-menu-trigger');
      setFamOpen(activeMenu, false);
      if (trigger) trigger.focus();
    }
  }

  /**
   * Global Keyboard Navigation Event Delegation for all Floating Action Menus
   */
  document.addEventListener('keydown', function (e) {
    const activeEl = document.activeElement;
    if (!activeEl) return;

    // Check if within a Floating Action Menu
    const menu = activeEl.closest('.ux4g-floating-action-menu');
    if (!menu) {
      // Global Escape handler
      if (e.key === 'Escape') {
        const openMenus = document.querySelectorAll('.ux4g-floating-action-menu.is-open');
        openMenus.forEach(m => setFamOpen(m, false));
      }
      return;
    }

    const trigger = menu.querySelector('.ux4g-fam-trigger, .ux4g-floating-action-menu-trigger');
    const items = Array.from(menu.querySelectorAll('.ux4g-fam-item'));
    const isOpen = menu.classList.contains('is-open');

    // ─── 1. Focus on Trigger FAB ───
    if (activeEl === trigger) {
      if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (!isOpen) {
          setFamOpen(menu, true, false, true); // Open and focus last item (closest to FAB)
        } else if (items.length > 0) {
          items[items.length - 1].focus();
        }
      } else if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (!isOpen) {
          setFamOpen(menu, true, true, false); // Open and focus first item (topmost)
        } else if (items.length > 0) {
          items[0].focus();
        }
      } else if (e.key === 'Escape' && isOpen) {
        e.preventDefault();
        setFamOpen(menu, false);
      } else if (e.key === 'Tab' && isOpen) {
        if (!e.shiftKey && items.length > 0) {
          e.preventDefault();
          items[0].focus();
        } else if (e.shiftKey && items.length > 0) {
          e.preventDefault();
          items[items.length - 1].focus();
        }
      }
    }

    // ─── 2. Focus on Menu Items ───
    else if (items.includes(activeEl)) {
      const currentIndex = items.indexOf(activeEl);

      if (e.key === 'ArrowUp' || e.key === 'ArrowLeft') {
        e.preventDefault();
        // Move UP towards index 0 (topmost)
        if (currentIndex > 0) {
          items[currentIndex - 1].focus();
        } else {
          // Wrap around to last item (closest to trigger)
          items[items.length - 1].focus();
        }
      } else if (e.key === 'ArrowDown' || e.key === 'ArrowRight') {
        e.preventDefault();
        // Move DOWN towards trigger
        if (currentIndex < items.length - 1) {
          items[currentIndex + 1].focus();
        } else {
          // From last item, go to trigger
          if (trigger) trigger.focus();
          else items[0].focus();
        }
      } else if (e.key === 'Home') {
        e.preventDefault();
        if (items.length > 0) items[0].focus();
      } else if (e.key === 'End') {
        e.preventDefault();
        if (items.length > 0) items[items.length - 1].focus();
      } else if (e.key === 'Escape') {
        e.preventDefault();
        setFamOpen(menu, false);
        if (trigger) trigger.focus();
      } else if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        activeEl.click();
      } else if (e.key === 'Tab') {
        if (!e.shiftKey && currentIndex === items.length - 1) {
          // Tab from last item moves to trigger
          e.preventDefault();
          if (trigger) trigger.focus();
        } else if (e.shiftKey && currentIndex === 0) {
          // Shift+Tab from first item moves to trigger
          e.preventDefault();
          if (trigger) trigger.focus();
        }
      }
    }
  });

  // 3. Close open menus when clicking outside
  document.addEventListener('click', function (e) {
    document.querySelectorAll('.ux4g-floating-action-menu.is-open').forEach(menu => {
      if (!menu.contains(e.target)) {
        setFamOpen(menu, false);
      }
    });
  });

  // Expose methods globally and in ux4g namespace
  window.setFamOpen = setFamOpen;
  window.toggleFam = toggleFam;
  window.toggleAllMenus = toggleAllMenus;
  window.handleItemClick = handleItemClick;

  if (window.ux4g) {
    window.ux4g.FloatingActionMenu = {
      open: (menu, focusFirst, focusLast) => setFamOpen(menu, true, focusFirst, focusLast),
      close: (menu) => setFamOpen(menu, false),
      toggle: toggleFam,
      toggleAll: toggleAllMenus
    };
  }
})(typeof window !== 'undefined' ? window : this, typeof document !== 'undefined' ? document : null);




