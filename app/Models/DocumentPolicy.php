<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DocumentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the document.
     */
    public function view(User $user, Document $document)
    {
        // Admin can view all documents
        if ($user->role === 'admin') {
            return true;
        }

        // Validator can view all documents
        if ($user->role === 'validator') {
            return true;
        }

        // Students can only view their own documents
        if ($user->role === 'student') {
            return $document->application && $document->application->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can download the document.
     */
    public function download(User $user, Document $document)
    {
        return $this->view($user, $document);
    }
}