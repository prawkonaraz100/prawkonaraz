@extends('layouts.public-content')

@php
    $heroScene = \Illuminate\Support\Facades\Vite::asset('resources/images/home/hero-composite-v3.webp');
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

@section('site_header')
    <x-site.home-header />
@endsection

@section('content')
    @include('home.partials.desktop-story')
@endsection
