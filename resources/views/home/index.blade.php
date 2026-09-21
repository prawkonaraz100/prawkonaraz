@extends('layouts.public-content')

@php
    $heroScene = \Illuminate\Support\Facades\Vite::asset('resources/images/home/hero-composite-v3.webp');
    $mobileAppScreen = \Illuminate\Support\Facades\Vite::asset('resources/images/home/hero-mobile-optimized.webp');
    $mobileAppBanner = \Illuminate\Support\Facades\Vite::asset('resources/images/home/mobile-app-banner-optimized.webp');
    $proofDashboard = \Illuminate\Support\Facades\Vite::asset('resources/images/home/proof/dashboard-optimized.webp');
    $proofExplanation = \Illuminate\Support\Facades\Vite::asset('resources/images/home/proof/explanation-optimized.webp');
    $proofExam = \Illuminate\Support\Facades\Vite::asset('resources/images/home/proof/exam-optimized.webp');
    $proofIncorrectQuestions = \Illuminate\Support\Facades\Vite::asset('resources/images/home/proof/incorrect-questions-optimized.webp');
    $proofMemoryTrainer = \Illuminate\Support\Facades\Vite::asset('resources/images/home/proof/memory-trainer-optimized.webp');
    $classicLearningScreen = \Illuminate\Support\Facades\Vite::asset('resources/images/home/learning/classic-mode.png');
    $focusLearningScreen = \Illuminate\Support\Facades\Vite::asset('resources/images/home/learning/focus-mode.png');
    $explanationFocusScreen = \Illuminate\Support\Facades\Vite::asset('resources/images/home/learning/explanation-focus.png');
    $explanationsOverviewScreen = \Illuminate\Support\Facades\Vite::asset('resources/images/home/learning/explanations-overview.png');
    $mistakesLearningVideo = \Illuminate\Support\Facades\Vite::asset('resources/videos/home/mistakes-learning-800p.mp4');
    $mistakesLearningPoster = \Illuminate\Support\Facades\Vite::asset('resources/images/home/mistakes-learning-poster-optimized.webp');
    $contactAdvisorImages = [
        1 => \Illuminate\Support\Facades\Vite::asset('resources/images/home/contact/advisor-monday-optimized.webp'),
        2 => \Illuminate\Support\Facades\Vite::asset('resources/images/home/contact/advisor-tuesday-optimized.webp'),
        3 => \Illuminate\Support\Facades\Vite::asset('resources/images/home/contact/advisor-wednesday-optimized.webp'),
        4 => \Illuminate\Support\Facades\Vite::asset('resources/images/home/contact/advisor-thursday-optimized.webp'),
        5 => \Illuminate\Support\Facades\Vite::asset('resources/images/home/contact/advisor-friday-optimized.webp'),
        6 => \Illuminate\Support\Facades\Vite::asset('resources/images/home/contact/advisor-saturday-optimized.webp'),
        7 => \Illuminate\Support\Facades\Vite::asset('resources/images/home/contact/advisor-sunday-optimized.webp'),
    ];
    $contactAdvisor['image'] = $contactAdvisorImages[$contactAdvisor['day_index']];
@endphp

@section('site_header')
    <x-site.home-header />
@endsection

@section('content')
    @include('home.partials.desktop-story')
@endsection
