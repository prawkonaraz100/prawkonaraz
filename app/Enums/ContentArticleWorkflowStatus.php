<?php

namespace App\Enums;

enum ContentArticleWorkflowStatus: string
{
    case Draft = 'draft';
    case InReview = 'in_review';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case NeedsReview = 'needs_review';
    case Archived = 'archived';
    case Withdrawn = 'withdrawn';
}
