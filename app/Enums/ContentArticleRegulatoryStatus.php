<?php

namespace App\Enums;

enum ContentArticleRegulatoryStatus: string
{
    case NotApplicable = 'not_applicable';
    case Proposal = 'proposal';
    case Consultation = 'consultation';
    case OfficialAnnouncement = 'official_announcement';
    case AdoptedFuture = 'adopted_future';
    case InForce = 'in_force';
}
