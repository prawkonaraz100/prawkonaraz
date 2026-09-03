<?php

use App\Http\Controllers\AboutOrganizationController;
use App\Http\Controllers\AccessActivationController;
use App\Http\Controllers\AdminBackupFileController;
use App\Http\Controllers\AdminLegalContentQuestionReferenceController;
use App\Http\Controllers\AdminLegalUnitSearchController;
use App\Http\Controllers\AdminMediaController;
use App\Http\Controllers\AdminPjmReportController;
use App\Http\Controllers\AdminQuestionCollectionPreviewController;
use App\Http\Controllers\AdminQuestionController;
use App\Http\Controllers\AdminQuestionExplanationController;
use App\Http\Controllers\AdminQuestionLegalReferenceController;
use App\Http\Controllers\AdminQuestionPromptController;
use App\Http\Controllers\AdminQuestionPublicExplanationController;
use App\Http\Controllers\ApiCategoryController;
use App\Http\Controllers\ApiLearningHomeController;
use App\Http\Controllers\ApiRankedMatchController;
use App\Http\Controllers\ApiRankedQueueController;
use App\Http\Controllers\ApiRankedStreamController;
use App\Http\Controllers\ApiStudySessionAnswerController;
use App\Http\Controllers\ApiStudySessionController;
use App\Http\Controllers\Auth\CsrfTokenController;
use App\Http\Controllers\Auth\ForcedPasswordChangeController;
use App\Http\Controllers\Auth\GoogleIdentityLoginController;
use App\Http\Controllers\Auth\PublicAuthDrawerController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Auth\TemporaryAccountClaimController;
use App\Http\Controllers\CategoryAnalyticsController;
use App\Http\Controllers\CategoryAnalyticsPageController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactPageController;
use App\Http\Controllers\ContentAuthorController;
use App\Http\Controllers\DashboardApiController;
use App\Http\Controllers\FriendInvitationClaimController;
use App\Http\Controllers\FriendInvitationOwnerController;
use App\Http\Controllers\HealthApiController;
use App\Http\Controllers\HomePageController;
use App\Http\Controllers\HowItWorksPageController;
use App\Http\Controllers\IncorrectQuestionController;
use App\Http\Controllers\LegalContentController;
use App\Http\Controllers\LlmsTextController;
use App\Http\Controllers\MeProfileController;
use App\Http\Controllers\MethodologyPageController;
use App\Http\Controllers\ModeratorAccountsController;
use App\Http\Controllers\PartnersPageController;
use App\Http\Controllers\PjmSessionPageController;
use App\Http\Controllers\PostAuthRedirectController;
use App\Http\Controllers\PricingPageController;
use App\Http\Controllers\ProfileAvatarController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileSocialAccountController;
use App\Http\Controllers\PublicDemoStudyController;
use App\Http\Controllers\PublicQuestionDatabaseController;
use App\Http\Controllers\PublicQuestionDifficultyController;
use App\Http\Controllers\PublicQuestionTopicController;
use App\Http\Controllers\QuestionCollectionLearningController;
use App\Http\Controllers\RankedSessionPageController;
use App\Http\Controllers\ReviewQueueController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SessionPageController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SitemapQuestionsController;
use App\Http\Controllers\StudySessionAnswerController;
use App\Http\Controllers\StudySessionController;
use App\Http\Controllers\TrafficSignCategoryController;
use App\Http\Controllers\TrafficSignHubController;
use App\Http\Controllers\TrafficSignLearningController;
use App\Http\Controllers\TrafficSignShowController;
use App\Http\Controllers\TrafficSignSupportingPageController;
use App\Http\Middleware\EnsureUserIsNotBanned;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\MarkReturningUser;
use App\Http\Middleware\TrackUserIpActivity;
use App\Models\Question;
use App\Models\StudySession;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Inertia\Inertia;

Route::get('/', HomePageController::class)->name('home');

Route::get('/auth/csrf-token', CsrfTokenController::class)
    ->name('auth.csrf-token');
Route::get('/auth/drawers/{drawer}', PublicAuthDrawerController::class)
    ->whereIn('drawer', ['login', 'register'])
    ->middleware(['guest', 'throttle:30,1'])
    ->name('public.auth-drawers.show');

Route::get('/.well-known/assetlinks.json', function () {
    return response()
        ->json(config('pwa.assetlinks.statements', []), 200, [], JSON_UNESCAPED_SLASHES)
        ->header('Cache-Control', 'public, max-age=300, must-revalidate');
})
    ->withoutMiddleware([
        EnsureUserIsNotBanned::class,
        HandleInertiaRequests::class,
        MarkReturningUser::class,
        TrackUserIpActivity::class,
        VerifyCsrfToken::class,
        StartSession::class,
        ShareErrorsFromSession::class,
    ])
    ->name('pwa.assetlinks');

Route::post('/auth/google/identity', [GoogleIdentityLoginController::class, 'store'])
    ->middleware(['guest', 'throttle:20,1'])
    ->name('google.identity.login');
Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
    ->name('social.redirect');
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->name('social.callback');
Route::get('/profile/delete-confirmation/{user}/{hash}', [ProfileController::class, 'confirmDeletion'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('profile.deletion.confirm');
Route::post('/profile/delete-confirmation/{user}/{hash}', [ProfileController::class, 'destroyViaSignedLink'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('profile.deletion.destroy');
Route::get('/profile/email-change-confirmation/{user}/{hash}', [ProfileController::class, 'confirmEmailChange'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('profile.email-change.confirm');
Route::post('/profile/email-change-confirmation/{user}/{hash}', [ProfileController::class, 'updateEmailViaSignedLink'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('profile.email-change.update');

Route::get('/zaproszenie', [FriendInvitationClaimController::class, 'codeForm'])
    ->name('friend-invitations.code.create');
Route::post('/zaproszenie/kod', [FriendInvitationClaimController::class, 'submitCode'])
    ->middleware('throttle:10,1')
    ->name('friend-invitations.code.store');
Route::middleware('auth')->group(function () {
    Route::get('/zaproszenie/potwierdz', [FriendInvitationClaimController::class, 'showPending'])
        ->name('friend-invitations.pending.show');
    Route::post('/zaproszenie/potwierdz', [FriendInvitationClaimController::class, 'accept'])
        ->middleware('verified')
        ->name('friend-invitations.pending.accept');
});
Route::get('/zaproszenie/{token}', [FriendInvitationClaimController::class, 'showToken'])
    ->where('token', '[A-Za-z0-9]+')
    ->name('friend-invitations.show-token');

Route::get('/testy-na-prawo-jazdy', [PublicDemoStudyController::class, 'landing'])
    ->name('public.tests');
Route::get('/testy-na-prawo-jazdy/demo', [PublicDemoStudyController::class, 'show'])
    ->name('public.tests.demo.show');
Route::post('/testy-na-prawo-jazdy/demo/answers', [PublicDemoStudyController::class, 'answer'])
    ->middleware('throttle:60,1')
    ->name('public.tests.demo.answers.store');
Route::post('/testy-na-prawo-jazdy/demo/complete', [PublicDemoStudyController::class, 'complete'])
    ->middleware('throttle:20,1')
    ->name('public.tests.demo.complete');
Route::post('/testy-na-prawo-jazdy/demo/restart', [PublicDemoStudyController::class, 'restart'])
    ->middleware('throttle:20,1')
    ->name('public.tests.demo.restart');
Route::get('/kurs', fn () => Inertia::render('Public/MarketingPlaceholder', [
    'title' => 'Kurs',
    'eyebrow' => 'Oferta',
    'description' => 'Ta sekcja została przygotowana jako pusta strona kursu, żebyśmy mogli podpiąć tu właściwy układ i treści w kolejnym kroku.',
]))->name('public.course');
Route::get('/wyklady', fn () => Inertia::render('Public/MarketingPlaceholder', [
    'title' => 'Wykłady',
    'eyebrow' => 'Materiały',
    'description' => 'Wykłady mają już własną trasę i pusty ekran startowy, gotowy do dalszego wypełnienia treścią.',
]))->name('public.lectures');
Route::get('/szkolenia-z-instruktorem', fn () => Inertia::render('Public/MarketingPlaceholder', [
    'title' => 'Szkolenia z instruktorem',
    'eyebrow' => 'Wsparcie',
    'description' => 'To miejsce jest przygotowane pod ofertę szkoleń z instruktorem i dalszą prezentację szczegółów współpracy.',
]))->name('public.instructor-training');
Route::get('/aktualnosci', fn () => Inertia::render('Public/MarketingPlaceholder', [
    'title' => 'Aktualności',
    'eyebrow' => 'Serwis',
    'description' => 'Tu pojawią się aktualności dla kandydatów, kursantów i instruktorów prawa jazdy.',
]))->name('public.news');
Route::get('/reklama', fn () => Inertia::render('Public/MarketingPlaceholder', [
    'title' => 'Reklama',
    'eyebrow' => 'Współpraca',
    'description' => 'To miejsce jest przygotowane pod informacje o reklamie i współpracy partnerskiej.',
]))->name('public.advertising');
Route::get('/partnerzy', PartnersPageController::class)
    ->name('public.partners');
Route::get('/przepisy', [LegalContentController::class, 'index'])
    ->name('public.regulations');
Route::get('/przepisy/{slug}', [LegalContentController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('public.regulations.show');
Route::get('/metodologia/przepisy-i-podstawy-prawne', [LegalContentController::class, 'methodology'])
    ->name('public.regulations.methodology');
Route::get('/poradniki', fn () => Inertia::render('Public/MarketingPlaceholder', [
    'title' => 'Poradniki',
    'eyebrow' => 'Nauka',
    'description' => 'W poradnikach zbierzemy praktyczne materiały pomagające przejść od teorii do pewnego wyniku na egzaminie.',
]))->name('public.guides');
Route::get('/spolecznosc', fn () => Inertia::render('Public/MarketingPlaceholder', [
    'title' => 'Społeczność',
    'eyebrow' => 'Użytkownicy',
    'description' => 'Sekcja społeczności jest przygotowana pod przyszłe funkcje dla kursantów i instruktorów.',
]))->name('public.community');
Route::get('/statystyki', fn () => Inertia::render('Public/MarketingPlaceholder', [
    'title' => 'Statystyki',
    'eyebrow' => 'Analityka',
    'description' => 'Publiczna strona statystyk została przygotowana jako pusty ekran startowy, niezależny od zalogowanej analityki kategorii.',
]))->name('public.statistics');
Route::get('/cennik', PricingPageController::class)
    ->name('public.pricing');

Route::get('/najtrudniejsze-pytania', fn () => to_route('public.hardest-questions.index'));
Route::get('/najtrudniejsze-pytania-na-prawo-jazdy', [PublicQuestionDifficultyController::class, 'index'])
    ->name('public.hardest-questions.index');
Route::get('/najtrudniejsze-pytania-na-prawo-jazdy/kategoria/{categorySlug}', [PublicQuestionDifficultyController::class, 'showCategory'])
    ->name('public.hardest-questions.categories.show');
Route::get('/oficjalna-baza-pytan-na-prawo-jazdy', [PublicQuestionDatabaseController::class, 'index'])
    ->name('public.questions.hub');
Route::get('/oficjalna-baza-pytan-na-prawo-jazdy/szukaj', [PublicQuestionDatabaseController::class, 'search'])
    ->name('public.questions.search');
Route::get('/oficjalna-baza-pytan-na-prawo-jazdy/temat/{topicSlug}', PublicQuestionTopicController::class)
    ->where('topicSlug', '[a-z0-9-]+')
    ->name('public.questions.topics.show');
Route::get('/oficjalna-baza-pytan-na-prawo-jazdy/{categorySlug}/pytanie/{externalId}/{slug?}', [PublicQuestionDatabaseController::class, 'showInCategory'])
    ->name('public.questions.category.show');
Route::get('/oficjalna-baza-pytan-na-prawo-jazdy/{categorySlug}', [PublicQuestionDatabaseController::class, 'category'])
    ->name('public.questions.category');
Route::get('/pytanie/{externalId}/{slug?}', [PublicQuestionDatabaseController::class, 'show'])
    ->name('public.questions.show');
Route::get('/robots.txt', RobotsController::class)
    ->name('robots');
Route::get('/llms.txt', LlmsTextController::class)
    ->name('llms');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])
    ->name('sitemap.index');
Route::get('/sitemaps/static.xml', [SitemapController::class, 'staticPages'])
    ->name('sitemap.static');
Route::get('/sitemaps/traffic-signs.xml', [SitemapController::class, 'signs'])
    ->name('sitemap.signs');
Route::get('/sitemaps/traffic-sign-supporting-pages.xml', [SitemapController::class, 'supportingPages'])
    ->name('sitemap.supporting-pages');
Route::get('/sitemaps/traffic-sign-categories.xml', [SitemapController::class, 'categories'])
    ->name('sitemap.categories');
Route::get('/sitemaps/question-hub.xml', [SitemapQuestionsController::class, 'hub'])
    ->name('sitemap.questions.hub');
Route::get('/sitemaps/question-categories.xml', [SitemapQuestionsController::class, 'categories'])
    ->name('sitemap.questions.categories');
Route::get('/sitemaps/question-topics.xml', [SitemapQuestionsController::class, 'topics'])
    ->name('sitemap.questions.topics');
Route::get('/sitemaps/questions.xml', [SitemapQuestionsController::class, 'index'])
    ->name('sitemap.questions');
Route::get('/sitemaps/questions-{categorySlug}.xml', [SitemapQuestionsController::class, 'category'])
    ->where('categorySlug', '[a-z0-9-]+')
    ->name('sitemap.questions.category');
Route::get('/sitemaps/videos.xml', [SitemapQuestionsController::class, 'videos'])
    ->name('sitemap.videos');
Route::get('/sitemaps/authors.xml', [SitemapController::class, 'authors'])
    ->name('sitemap.authors');
Route::get('/sitemaps/legal-content.xml', [SitemapController::class, 'legalContent'])
    ->name('sitemap.legal-content');
Route::get('/znaki-drogowe', TrafficSignHubController::class)
    ->name('traffic-signs.index');
Route::get('/znaki-drogowe/kategorie/{categorySlug}', TrafficSignCategoryController::class)
    ->name('traffic-signs.categories.show');
Route::get('/znaki-drogowe/porownania/{supportingPageSlug}', TrafficSignSupportingPageController::class)
    ->name('traffic-signs.supporting.show');
Route::get('/znaki-drogowe/{signSlug}', TrafficSignShowController::class)
    ->name('traffic-signs.show');
Route::get('/autorzy/{authorSlug}', ContentAuthorController::class)
    ->name('content-authors.show');
Route::redirect('/o-serwisie', '/o-nas', 301);
Route::get('/o-nas', AboutOrganizationController::class)
    ->name('about.organization');
Route::get('/jak-to-dziala', HowItWorksPageController::class)
    ->name('about.how-it-works');
Route::get('/kontakt', ContactPageController::class)
    ->name('about.contact');
Route::get('/metodologia', MethodologyPageController::class)
    ->name('about.methodology');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', PostAuthRedirectController::class)
        ->name('dashboard');
    Route::get('/konto/przejmij', [TemporaryAccountClaimController::class, 'edit'])
        ->name('account.claim.edit');
    Route::put('/konto/przejmij', [TemporaryAccountClaimController::class, 'update'])
        ->name('account.claim.update');
    Route::get('/konto/zmien-haslo-startowe', [ForcedPasswordChangeController::class, 'edit'])
        ->name('password.force.edit');
    Route::put('/konto/zmien-haslo-startowe', [ForcedPasswordChangeController::class, 'update'])
        ->name('password.force.update');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/aktywuj-dostep', AccessActivationController::class)
        ->name('access.activate');

    Route::middleware('moderator.panel')->prefix('moderator')->name('moderator.')->group(function () {
        Route::get('/konta', [ModeratorAccountsController::class, 'index'])
            ->name('accounts.index');
        Route::post('/konta', [ModeratorAccountsController::class, 'store'])
            ->name('accounts.store');
        Route::post('/konta/{account}/haslo-startowe', [ModeratorAccountsController::class, 'regenerateStartPassword'])
            ->name('accounts.start-password.regenerate');
    });

    Route::prefix('checkout')->name('checkout.')->group(function () {
        Route::post('/plany/{plan}', [CheckoutController::class, 'store'])
            ->name('store');
        Route::get('/{order}', [CheckoutController::class, 'show'])
            ->name('show');
        Route::post('/{order}/sandbox/potwierdz', [CheckoutController::class, 'completeSandbox'])
            ->name('sandbox.complete');
        Route::post('/{order}/anuluj', [CheckoutController::class, 'cancel'])
            ->name('cancel');
        Route::get('/{order}/sukces', [CheckoutController::class, 'success'])
            ->name('success');
    });

    Route::get('/nauka', SessionPageController::class)
        ->name('session.index');

    Route::middleware('pjm.access')->group(function () {
        Route::get('/nauka/pjm', PjmSessionPageController::class)
            ->name('session.pjm');
        Route::post('/nauka/pjm', [PjmSessionPageController::class, 'store'])
            ->name('session.pjm.store');
    });

    Route::middleware('product.access')->group(function () {
        Route::get('/moje-postepy', fn () => to_route('session.index'));
        Route::get('/session', fn () => to_route('session.index'));
        Route::get('/nauka/znaki-drogowe', [TrafficSignLearningController::class, 'index'])
            ->name('session.traffic-signs');
        Route::post('/nauka/znaki-drogowe', [TrafficSignLearningController::class, 'store'])
            ->name('traffic-sign-learning.store');
        Route::get('/nauka/znaki-drogowe/teraz', [TrafficSignLearningController::class, 'current'])
            ->name('traffic-sign-learning.current');
        Route::post('/nauka/znaki-drogowe/odpowiedzi', [TrafficSignLearningController::class, 'answer'])
            ->name('traffic-sign-learning.answers.store');
        Route::post('/nauka/znaki-drogowe/odpowiedzi/sync', [TrafficSignLearningController::class, 'syncAnswers'])
            ->name('traffic-sign-learning.answers.sync');
        Route::get('/nauka/znaki-drogowe/wynik/{trafficSignLearningSession}', [TrafficSignLearningController::class, 'result'])
            ->name('traffic-sign-learning.results.show');
        Route::get('/nauka/kursy/{questionCollection:slug}', [QuestionCollectionLearningController::class, 'show'])
            ->name('learning.question-collections.show');
        Route::post('/nauka/kursy/{questionCollection:slug}/moduly/{module:slug}/uruchom', [QuestionCollectionLearningController::class, 'start'])
            ->name('learning.question-collections.modules.start');
        Route::get('/nauka/kursy/{questionCollection:slug}/bledne-pytania', [QuestionCollectionLearningController::class, 'incorrectQuestions'])
            ->name('learning.question-collections.incorrect-questions.index');
        Route::post('/nauka/kursy/{questionCollection:slug}/bledne-pytania/powtorz', [QuestionCollectionLearningController::class, 'startIncorrectQuestions'])
            ->name('learning.question-collections.incorrect-questions.start');
        Route::delete('/nauka/kursy/{questionCollection:slug}/bledne-pytania/{courseIncorrect}', [QuestionCollectionLearningController::class, 'destroyIncorrectQuestion'])
            ->name('learning.question-collections.incorrect-questions.destroy');
        Route::patch('/nauka/kursy/{questionCollection:slug}/bledne-pytania/ustawienia/automatyczne-usuwanie', [QuestionCollectionLearningController::class, 'updateIncorrectQuestionPreference'])
            ->name('learning.question-collections.incorrect-questions.preference.update');
        Route::get('/nauka/ranking', [RankedSessionPageController::class, 'lobby'])
            ->name('session.ranking');
        Route::get('/nauka/ranking/oczekiwanie', [RankedSessionPageController::class, 'waiting'])
            ->name('session.ranking.waiting');
        Route::get('/nauka/ranking/mecz', [RankedSessionPageController::class, 'match'])
            ->name('session.ranking.match');
        Route::get('/nauka/ranking/wynik', [RankedSessionPageController::class, 'result'])
            ->name('session.ranking.result');
        Route::get('/questions', function () {
            return to_route('session.index');
        })->name('questions.index');
        Route::get('/analytics/categories/{licenseCategory}', CategoryAnalyticsPageController::class)
            ->name('analytics.categories.show');
        Route::get('/review-queue', fn (Request $request) => to_route('review-queue.index', $request->query()));
        Route::get('/trener-pamieci', [ReviewQueueController::class, 'index'])
            ->name('review-queue.index');
        Route::get('/nauka/bledne-pytania', [IncorrectQuestionController::class, 'index'])
            ->name('incorrect-questions.index');
        Route::delete('/nauka/bledne-pytania/{userIncorrectQuestion}', [IncorrectQuestionController::class, 'destroy'])
            ->name('incorrect-questions.destroy');
        Route::patch('/nauka/bledne-pytania/ustawienia/automatyczne-usuwanie', [IncorrectQuestionController::class, 'updatePreference'])
            ->name('incorrect-questions.preference.update');
        Route::post('/study-sessions', [StudySessionController::class, 'store'])
            ->name('study-sessions.store');
    });

    Route::middleware('study.session.access')->group(function () {
        Route::get('/session/current', fn () => to_route('study-sessions.current'));
        Route::get('/session/current/questions', fn (Request $request) => to_route(
            'study-sessions.current.questions.index',
            $request->query(),
        ));
        Route::get('/session/current/questions/{question}', fn (Question $question) => to_route(
            'study-sessions.current.questions.show',
            $question,
        ));
        Route::post('/session/current/answers', [StudySessionAnswerController::class, 'storeCurrent']);
        Route::post('/session/current/complete', [StudySessionController::class, 'completeCurrent']);
        Route::get('/study-sessions/{studySession}', fn (StudySession $studySession) => to_route(
            'study-sessions.show',
            $studySession,
        ));
        Route::get('/study-sessions/{studySession}/questions', fn (Request $request, StudySession $studySession) => to_route(
            'study-sessions.questions.index',
            ['studySession' => $studySession, ...$request->query()],
        ));
        Route::get('/study-sessions/{studySession}/questions/{question}', fn (StudySession $studySession, Question $question) => to_route(
            'study-sessions.questions.show',
            ['studySession' => $studySession, 'question' => $question],
        ));
        Route::post('/study-sessions/{studySession}/complete', [StudySessionController::class, 'complete']);
        Route::post('/study-sessions/{studySession}/answers', [StudySessionAnswerController::class, 'store']);
        Route::get('/nauka/teraz', [StudySessionController::class, 'current'])
            ->name('study-sessions.current');
        Route::post('/nauka/teraz/egzamin/stan', [StudySessionController::class, 'currentExamState'])
            ->name('study-sessions.current.exam.state');
        Route::get('/nauka/teraz/pytania', [StudySessionController::class, 'currentQuestionBatchData'])
            ->name('study-sessions.current.questions.index');
        Route::get('/nauka/teraz/pytania/{question}', [StudySessionController::class, 'currentQuestionData'])
            ->name('study-sessions.current.questions.show');
        Route::post('/nauka/teraz/odpowiedzi', [StudySessionAnswerController::class, 'storeCurrent'])
            ->name('study-sessions.current.answers.store');
        Route::post('/nauka/teraz/zakoncz', [StudySessionController::class, 'completeCurrent'])
            ->name('study-sessions.current.complete');
        Route::get('/nauka/wynik/{studySession}', [StudySessionController::class, 'show'])
            ->name('study-sessions.show');
        Route::get('/nauka/wynik/{studySession}/pytania', [StudySessionController::class, 'questionBatchData'])
            ->name('study-sessions.questions.index');
        Route::get('/nauka/wynik/{studySession}/pytania/{question}', [StudySessionController::class, 'questionData'])
            ->name('study-sessions.questions.show');
        Route::post('/nauka/wynik/{studySession}/zakoncz', [StudySessionController::class, 'complete'])
            ->name('study-sessions.complete');
        Route::post('/nauka/wynik/{studySession}/odpowiedzi', [StudySessionAnswerController::class, 'store'])
            ->name('study-sessions.answers.store');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/avatar', [ProfileAvatarController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('profile.avatar.store');
    Route::delete('/profile/avatar', [ProfileAvatarController::class, 'destroy'])
        ->name('profile.avatar.destroy');
    Route::patch('/profile/product', [ProfileController::class, 'updateProductProfile'])
        ->name('profile.product.update');
    Route::post('/profile/zaproszenia', [FriendInvitationOwnerController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('friend-invitations.store');
    Route::delete('/profile/zaproszenia/{friendInvitation}', [FriendInvitationOwnerController::class, 'destroy'])
        ->name('friend-invitations.destroy');
    Route::delete('/profile/social/{provider}', [ProfileSocialAccountController::class, 'destroy'])
        ->name('profile.social.destroy');
    Route::post('/profile/delete-confirmation', [ProfileController::class, 'sendDeletionConfirmation'])
        ->middleware('throttle:3,1')
        ->name('profile.deletion.send');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->prefix('admin/backupy')->name('admin.backups.')->group(function () {
    Route::get('/pobierz', [AdminBackupFileController::class, 'download'])
        ->name('download');
});

Route::middleware(['auth', 'verified'])->prefix('admin/kolekcje-pytan')->name('admin.question-collections.')->group(function () {
    Route::get('/', [AdminQuestionCollectionPreviewController::class, 'index'])
        ->name('index');
    Route::patch('/{questionCollection:slug}/dostepnosc', [AdminQuestionCollectionPreviewController::class, 'updateAvailability'])
        ->name('availability.update');
    Route::post('/{questionCollection:slug}/moduly/{module:slug}/uruchom', [AdminQuestionCollectionPreviewController::class, 'start'])
        ->name('modules.start');
});

Route::middleware(['auth', 'verified'])->prefix('admin/pjm')->name('admin.pjm.')->group(function () {
    Route::get('/raport/{format}', AdminPjmReportController::class)
        ->whereIn('format', ['json', 'csv'])
        ->name('report.download');
});

Route::middleware(['auth', 'verified'])->prefix('/api/v1')->group(function () {
    Route::post('/admin/media/presign', [AdminMediaController::class, 'presign'])
        ->middleware('throttle:30,1')
        ->name('api.v1.admin.media.presign');
    Route::post('/admin/media/confirm', [AdminMediaController::class, 'confirm'])
        ->middleware('throttle:30,1')
        ->name('api.v1.admin.media.confirm');
    Route::post('/admin/questions', [AdminQuestionController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('api.v1.admin.questions.store');
    Route::patch('/admin/questions/{question}/prompt', [AdminQuestionPromptController::class, 'update'])
        ->middleware('throttle:30,1')
        ->name('api.v1.admin.questions.prompt.update');
    Route::patch('/admin/questions/{question}/explanation', [AdminQuestionExplanationController::class, 'update'])
        ->middleware('throttle:30,1')
        ->name('api.v1.admin.questions.explanation.update');
    Route::patch('/admin/questions/{question}/public-explanation', [AdminQuestionPublicExplanationController::class, 'update'])
        ->middleware('throttle:30,1')
        ->name('api.v1.admin.questions.public-explanation.update');
    Route::patch('/admin/questions/{question}/legal-reference', [AdminQuestionLegalReferenceController::class, 'update'])
        ->middleware('throttle:30,1')
        ->name('api.v1.admin.questions.legal-reference.update');
    Route::get('/admin/legal-units/search', [AdminLegalUnitSearchController::class, 'index'])
        ->middleware('throttle:60,1')
        ->name('api.v1.admin.legal-units.search');
    Route::get('/admin/legal-content-pages/{legalContentPage}/question-references', [AdminLegalContentQuestionReferenceController::class, 'index'])
        ->middleware('throttle:60,1')
        ->name('api.v1.admin.legal-content-pages.question-references.index');
    Route::post('/admin/legal-content-pages/{legalContentPage}/question-references', [AdminLegalContentQuestionReferenceController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('api.v1.admin.legal-content-pages.question-references.store');
    Route::delete('/admin/legal-content-pages/{legalContentPage}/question-references/{questionLegalReference}', [AdminLegalContentQuestionReferenceController::class, 'destroy'])
        ->middleware('throttle:30,1')
        ->name('api.v1.admin.legal-content-pages.question-references.destroy');
    Route::patch('/admin/questions/{question}/media/reorder', [AdminMediaController::class, 'reorder'])
        ->middleware('throttle:30,1')
        ->name('api.v1.admin.questions.media.reorder');
    Route::delete('/admin/media/{questionMedia}', [AdminMediaController::class, 'destroy'])
        ->middleware('throttle:30,1')
        ->name('api.v1.admin.media.destroy');
    Route::get('/me/profile', [MeProfileController::class, 'show'])
        ->name('api.v1.me.profile.show');
    Route::put('/me/profile', [MeProfileController::class, 'update'])
        ->name('api.v1.me.profile.update');
    Route::get('/me/learning-home', ApiLearningHomeController::class)
        ->name('api.v1.me.learning-home');

    Route::middleware('product.access')->group(function () {
        Route::post('/sessions', [ApiStudySessionController::class, 'store'])
            ->name('api.v1.sessions.store');
        Route::middleware('study.session.access')->group(function () {
            Route::get('/sessions/current', [ApiStudySessionController::class, 'current'])
                ->name('api.v1.sessions.current');
            Route::get('/sessions/current/questions', [ApiStudySessionController::class, 'currentQuestions'])
                ->name('api.v1.sessions.current.questions.index');
            Route::get('/sessions/current/questions/{question}', [ApiStudySessionController::class, 'currentQuestion'])
                ->name('api.v1.sessions.current.questions.show');
            Route::post('/sessions/current/answers', [ApiStudySessionAnswerController::class, 'storeCurrent'])
                ->name('api.v1.sessions.current.answers.store');
            Route::post('/sessions/current/complete', [ApiStudySessionController::class, 'completeCurrent'])
                ->name('api.v1.sessions.current.complete');
            Route::post('/sessions/current/exam-state', [ApiStudySessionController::class, 'currentExamState'])
                ->name('api.v1.sessions.current.exam.state');
            Route::get('/sessions/{studySession}', [ApiStudySessionController::class, 'show'])
                ->name('api.v1.sessions.show');
            Route::post('/sessions/{studySession}/answers', [ApiStudySessionAnswerController::class, 'store'])
                ->name('api.v1.sessions.answers.store');
            Route::post('/sessions/{studySession}/complete', [ApiStudySessionController::class, 'complete'])
                ->name('api.v1.sessions.complete');
        });
        Route::get('/me/dashboard', DashboardApiController::class)
            ->name('api.v1.me.dashboard');
        Route::get('/me/analytics/categories/{licenseCategory}', CategoryAnalyticsController::class)
            ->name('api.v1.me.analytics.categories.show');
        Route::get('/me/review-queue', [ReviewQueueController::class, 'apiIndex'])
            ->name('api.v1.me.review-queue');
        Route::get('/me/incorrect-questions', [IncorrectQuestionController::class, 'apiIndex'])
            ->name('api.v1.me.incorrect-questions.index');
        Route::delete('/me/incorrect-questions/{userIncorrectQuestion}', [IncorrectQuestionController::class, 'apiDestroy'])
            ->name('api.v1.me.incorrect-questions.destroy');
        Route::patch('/me/incorrect-questions/preference', [IncorrectQuestionController::class, 'apiUpdatePreference'])
            ->name('api.v1.me.incorrect-questions.preference.update');
        Route::get('/ranked/overview', [ApiRankedQueueController::class, 'overview'])
            ->name('api.v1.ranked.overview');
        Route::get('/ranked/stream', ApiRankedStreamController::class)
            ->name('api.v1.ranked.stream');
        Route::get('/ranked/history', [ApiRankedMatchController::class, 'history'])
            ->name('api.v1.ranked.history');
        Route::post('/ranked/queue/join', [ApiRankedQueueController::class, 'join'])
            ->name('api.v1.ranked.queue.join');
        Route::post('/ranked/queue/leave', [ApiRankedQueueController::class, 'leave'])
            ->name('api.v1.ranked.queue.leave');
        Route::get('/ranked/matches/{rankedMatch:public_id}', [ApiRankedMatchController::class, 'show'])
            ->name('api.v1.ranked.matches.show');
        Route::get('/ranked/matches/{rankedMatch:public_id}/events', [ApiRankedMatchController::class, 'events'])
            ->name('api.v1.ranked.matches.events');
        Route::post('/ranked/matches/{rankedMatch:public_id}/ready', [ApiRankedMatchController::class, 'ready'])
            ->name('api.v1.ranked.matches.ready');
        Route::post('/ranked/matches/{rankedMatch:public_id}/abandon', [ApiRankedMatchController::class, 'abandon'])
            ->name('api.v1.ranked.matches.abandon');
        Route::post('/ranked/matches/{rankedMatch:public_id}/answers', [ApiRankedMatchController::class, 'answer'])
            ->name('api.v1.ranked.matches.answers.store');
        Route::post('/ranked/matches/{rankedMatch:public_id}/pong', [ApiRankedMatchController::class, 'pong'])
            ->name('api.v1.ranked.matches.pong');
    });
});

Route::prefix('/api/v1')->group(function () {
    Route::get('/health', HealthApiController::class)
        ->name('api.v1.health');
    Route::get('/categories', [ApiCategoryController::class, 'index'])
        ->name('api.v1.categories.index');
    Route::get('/categories/{licenseCategory}', [ApiCategoryController::class, 'show'])
        ->name('api.v1.categories.show');
});

require __DIR__.'/auth.php';
