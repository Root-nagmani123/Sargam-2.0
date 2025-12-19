var userSettings = {
  Layout: "vertical", // vertical | horizontal
  // Default to collapsed on first use; persist user choice in localStorage
  SidebarType: (function() {
    try {
      return localStorage.getItem('SidebarType') || "mini-sidebar";
    } catch (e) {
      return "mini-sidebar";
    }
  })(), // full | mini-sidebar
  BoxedLayout: true, // true | false
  Direction: "ltr", // ltr | rtl
  Theme: "light", // light | dark
  ColorTheme: "Blue_Theme", // Blue_Theme | Aqua_Theme | Purple_Theme | Green_Theme | Cyan_Theme | Orange_Theme
  cardBorder: false, // true | false
};
