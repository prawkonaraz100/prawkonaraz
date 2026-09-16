<?php

namespace App\Enums;

enum ContentArticleType: string
{
    case News = 'news';
    case Guide = 'guide';
    case Explainer = 'explainer';
    case Analysis = 'analysis';
    case Report = 'report';
}
