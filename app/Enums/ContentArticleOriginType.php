<?php

namespace App\Enums;

enum ContentArticleOriginType: string
{
    case Original = 'original';
    case Compiled = 'compiled';
    case OfficialSource = 'official_source';
    case DataAnalysis = 'data_analysis';
    case LicensedAgency = 'licensed_agency';
}
