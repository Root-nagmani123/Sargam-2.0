

<?php $__env->startSection('title', 'Programme - Sargam | Lal Bahadur'); ?>

<?php $__env->startSection('content'); ?>

<div class="container-fluid">
    <div class="card card-body py-3">
        <div class="row align-items-center">
            <div class="col-12">
                <div class="d-sm-flex align-items-center justify-space-between">
                    <h4 class="mb-4 mb-sm-0 card-title">Create Course</h4>
                    <nav aria-label="breadcrumb" class="ms-auto">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item d-flex align-items-center">
                                <a class="text-muted text-decoration-none d-flex" href="index.html">
                                    <iconify-icon icon="solar:home-2-line-duotone" class="fs-6"></iconify-icon>
                                </a>
                            </li>
                            <li class="breadcrumb-item" aria-current="page">
                                <span class="badge fw-medium fs-2 bg-primary-subtle text-primary">
                                    Course
                                </span>
                            </li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <!-- start Vertical Steps Example -->
    <div class="card">
        <div class="card-body">
            <h4 class="card-title mb-3">Create Course</h4>
            <hr>
            <form>
                <div class="row">
                    <div id="course_fields" class="my-2"></div>
                    <div class="row" id="course_fields">
                        <div class="col-sm-5">
                            <label for="Schoolname" class="form-label">School Name :</label>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="Schoolname" name="Schoolname"
                                    placeholder="School Name">
                            </div>
                        </div>
                        <div class="col-sm-5">
                            <label for="Schoolname" class="form-label">School Name :</label>
                            <div class="mb-3">
                                <input type="text" class="form-control" id="Age" name="Age" placeholder="Age">
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <label for="Schoolname" class="form-label"></label>
                            <div class="mb-3">
                                <button onclick="course_fields();" class="btn btn-success fw-medium" type="button">
                                    <i class="material-icons menu-icon">add</i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <button class="btn btn-primary hstack gap-6 float-end" type="submit">
                    <i class="material-icons menu-icon">send</i>
                        Submit
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- end Vertical Steps Example -->
</div>


<?php $__env->stopSection(); ?>
<?php echo $__env->make('admin.layouts.master', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp1\htdocs\Sargam-2.0\resources\views/admin/programme/create.blade.php ENDPATH**/ ?>