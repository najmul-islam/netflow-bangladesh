<?php

namespace App\Http\Controllers;

use App\Models\CourseEnrollment;
use App\Http\Requests\StoreCourseEnrollmentRequest;
use App\Http\Requests\UpdateCourseEnrollmentRequest;

class CourseEnrollmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCourseEnrollmentRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(CourseEnrollment $courseEnrollment)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CourseEnrollment $courseEnrollment)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCourseEnrollmentRequest $request, CourseEnrollment $courseEnrollment)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CourseEnrollment $courseEnrollment)
    {
        //
    }
}
