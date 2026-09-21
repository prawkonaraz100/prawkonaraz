@extends('layouts.public-content')

@push('styles')
    @vite('resources/css/home.css')
@endpush

@push('scripts')
    @vite('resources/js/home.ts')
@endpush

@php
    $heroScene = \Illuminate\Support\Facades\Vite::asset('resources/images/home/hero-composite-v3.webp');
    $mobileAppScreen = \Illuminate\Support\Facades\Vite::asset('resources/images/home/hero-mobile.png');
    $mobileAppBanner = \Illuminate\Support\Facades\Vite::asset('resources/images/home/mobile-app-banner.webp');
    $proofDashboard = \Illuminate\Support\Facades\Vite::asset('resources/images/home/proof/dashboard.webp');
    $proofExplanation = \Illuminate\Support\Facades\Vite::asset('resources/images/home/proof/explanation.webp');
    $proofExam = \Illuminate\Support\Facades\Vite::asset('resources/images/home/proof/exam.webp');
    $proofIncorrectQuestions = \Illuminate\Support\Facades\Vite::asset('resources/images/home/proof/incorrect-questions.webp');
    $proofMemoryTrainer = \Illuminate\Support\Facades\Vite::asset('resources/images/home/proof/memory-trainer.webp');
    $classicLearningScreen = \Illuminate\Support\Facades\Vite::asset('resources/images/home/learning/classic-mode.png');
    $focusLearningScreen = \Illuminate\Support\Facades\Vite::asset('resources/images/home/learning/focus-mode.png');
    $explanationFocusScreen = \Illuminate\Support\Facades\Vite::asset('resources/images/home/learning/explanation-focus.png');
    $explanationsOverviewScreen = \Illuminate\Support\Facades\Vite::asset('resources/images/home/learning/explanations-overview.png');
    $mistakesLearningVideo = \Illuminate\Support\Facades\Vite::asset('resources/videos/home/mistakes-learning-800p.mp4');
    $mistakesLearningPoster = \Illuminate\Support\Facades\Vite::asset('resources/images/home/mistakes-learning-poster.jpg');
    $contactAdvisorImages = [
        1 => \Illuminate\Support\Facades\Vite::asset('resources/images/home/contact/advisor-monday.jpg'),
        2 => \Illuminate\Support\Facades\Vite::asset('resources/images/home/contact/advisor-tuesday.jpg'),
        3 => \Illuminate\Support\Facades\Vite::asset('resources/images/home/contact/advisor-wednesday.jpg'),
        4 => \Illuminate\Support\Facades\Vite::asset('resources/images/home/contact/advisor-thursday.jpg'),
        5 => \Illuminate\Support\Facades\Vite::asset('resources/images/home/contact/advisor-friday.jpg'),
        6 => \Illuminate\Support\Facades\Vite::asset('resources/images/home/contact/advisor-saturday.jpg'),
        7 => \Illuminate\Support\Facades\Vite::asset('resources/images/home/contact/advisor-sunday.jpg'),
    ];
    $contactAdvisor['image'] = $contactAdvisorImages[$contactAdvisor['day_index']];
@endphp

@section('site_header')
    <x-site.home-header />
@endsection

@section('content')
    @include('home.partials.desktop-story')
@endsection
