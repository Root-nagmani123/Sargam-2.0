// let baseUrl = window.location.origin;
// Base URL for JS routes:
// - Always keep the CURRENT browser origin (prevents redirecting to wrong host/IP)
// - Add Laravel's base path for sub-folder installs (e.g. /Sargam-2.0/public)
var basePathMeta = document.querySelector('meta[name="app-base-path"]');
var basePath = (basePathMeta && basePathMeta.getAttribute('content')) ? basePathMeta.getAttribute('content') : '';
let baseUrl = (window.location.origin + basePath).replace(/\/+$/, '');
const routes = {
    'toggleStatus': baseUrl + '/admin/toggle-status',
    'groupMappingExcelUpload': baseUrl + '/group-mapping/import-group-mapping',
    'groupMappingGetGroupNamesByType': baseUrl + '/group-mapping/get-group-names-by-type',
    'groupMappingStudentList': baseUrl + '/group-mapping/student-list',
    'groupMappingStudentUpdate': baseUrl + '/group-mapping/student-update',
    'groupMappingStudentDelete': baseUrl + '/group-mapping/student-delete',
    'groupMappingSendMessage': baseUrl + '/group-mapping/send-message',
    'getStudentListAccordingToGroup': baseUrl + '/mdo-escrot-exemption/get-student-list-according-to-course',
    'getAttendanceList': baseUrl + '/attendance/get-attendance-list',
    'facultyStoreUrl': baseUrl + '/faculty/store',
    'facultyIndexUrl': baseUrl + '/faculty/',
    'facultyUpdateUrl': baseUrl + '/faculty/update',
    'getStatesByCountry': baseUrl + '/master/country/get-states-by-country',
    'getDistrictsByState': baseUrl + '/master/country/get-districts-by-state',
    'getCitiesByDistrict': baseUrl + '/master/country/get-cities-by-district',
    'assignHostelToStudent': baseUrl + '/hostel-building-map/assign-hostel-student',

    'getStudentAttendanceBytopic': baseUrl + '/admin/memo-notice-management/get-student-attendance-by-topic',
};
