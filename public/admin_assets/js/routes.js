// let baseUrl = window.location.origin;
// NOTE: Use Laravel's base URL (supports sub-folder installs like /Sargam-2.0/public).
// Falls back to window.location.origin if meta tag is missing.
var baseUrlMeta = document.querySelector('meta[name="app-base-url"]');
let baseUrl = (baseUrlMeta && baseUrlMeta.getAttribute('content')) ? baseUrlMeta.getAttribute('content') : window.location.origin;
baseUrl = baseUrl.replace(/\/+$/, '');
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
