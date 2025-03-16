<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    public function manage(User $user, Enrollment $enrollment)
    {
        return $user->id === $enrollment->student_id;
    }
}
