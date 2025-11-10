let baseUrl = window.location.origin;
const routes = {
    'toggleStatus': baseUrl + '/admin/toggle-status',
    'groupMappingExcelUpload': baseUrl + '/group-mapping/import-group-mapping',
    'groupMappingStudentList': baseUrl + '/group-mapping/student-list',
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