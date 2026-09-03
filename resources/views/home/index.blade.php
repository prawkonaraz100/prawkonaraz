@extends('layouts.public-content')

@php
    $heroPortrait = \Illuminate\Support\Facades\Vite::asset('resources/images/home/hero-driver-cutout-v4.webp');
    $heroProduct = \Illuminate\Support\Facades\Vite::asset('resources/images/home/hero-desktop.png');
    $mobileAppScreen = \Illuminate\Support\Facades\Vite::asset('resources/images/home/hero-mobile.png');
    $proofDashboard = \Illuminate\Support\Facades\Vite::asset('resources/images/home/proof/dashboard.webp');
    $proofExplanation = \Illuminate\Support\Facades\Vite::asset('resources/images/home/proof/explanation.webp');
    $proofExam = \Illuminate\Support\Facades\Vite::asset('resources/images/home/proof/exam.webp');
    $proofIncorrectQuestions = \Illuminate\Support\Facades\Vite::asset('resources/images/home/proof/incorrect-questions.webp');
    $proofMemoryTrainer = \Illuminate\Support\Facades\Vite::asset('resources/images/home/proof/memory-trainer.webp');
    $proofPjm = \Illuminate\Support\Facades\Vite::asset('resources/images/home/proof/pjm.webp');
    $proofRanking = \Illuminate\Support\Facades\Vite::asset('resources/images/home/proof/ranking.webp');
    $proofTrafficSigns = \Illuminate\Support\Facades\Vite::asset('resources/images/home/proof/traffic-signs.webp');
@endphp

@section('content')
    @include('home.partials.desktop-story')
@endsection
