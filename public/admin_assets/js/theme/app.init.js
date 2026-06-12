var userSettings = {
  Layout: "vertical", // vertical | horizontal
  // Default expanded; persist user choice in localStorage
  SidebarType: (function() {
    try {
      if (!localStorage.getItem('SidebarType_migrated_v2')) {
        localStorage.setItem('SidebarType', 'full');
        localStorage.setItem('SidebarType_migrated_v2', '1');
      }
      return localStorage.getItem('SidebarType') || "full";
    } catch (e) {
      return "full";
    }
  })(), // full | mini-sidebar
  BoxedLayout: true, // true | false
  Direction: "ltr", // ltr | rtl
  Theme: "light", // light | dark - Always forced to light mode
  ColorTheme: "Blue_Theme", // Blue_Theme | Aqua_Theme | Purple_Theme | Green_Theme | Cyan_Theme | Orange_Theme
  cardBorder: false, // true | false
};
