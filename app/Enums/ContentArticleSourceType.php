<?php

namespace App\Enums;

enum ContentArticleSourceType: string
{
    case Official = 'official';
    case Legislation = 'legislation';
    case Institution = 'institution';
    case PrimaryData = 'primary_data';
    case Interview = 'interview';
    case Report = 'report';
    case Media = 'media';
    case Other = 'other';
}
