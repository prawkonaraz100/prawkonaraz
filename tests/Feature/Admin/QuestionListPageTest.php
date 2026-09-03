<?php

use App\Filament\Resources\Questions\Pages\ListQuestions;
use App\Models\Question;
use App\Models\QuestionExplanationAnnotation;
use App\Models\QuestionExplanationAsset;
use App\Models\User;
use Livewire\Livewire;

test('admin can filter questions by visual markers in the questions list', function () {
    $admin = User::factory()->admin()->create();

    $questionWithMarkers = Question::factory()->create([
        'external_id' => 'MARKERS-YES',
        'prompt' => 'Pytanie z markerami wizualnymi',
    ]);

    $questionWithoutMarkers = Question::factory()->create([
        'external_id' => 'MARKERS-NO',
        'prompt' => 'Pytanie bez markerów wizualnych',
    ]);

    QuestionExplanationAnnotation::factory()
        ->for($questionWithMarkers, 'question')
        ->create([
            'position' => 1,
        ]);

    $this->actingAs($admin);

    Livewire::test(ListQuestions::class)
        ->assertTableFilterExists('has_visual_markers')
        ->assertTableColumnExists('explanation_annotations_count')
        ->assertTableColumnStateSet('explanation_annotations_count', 1, $questionWithMarkers)
        ->assertTableColumnStateSet('explanation_annotations_count', 0, $questionWithoutMarkers)
        ->assertCanSeeTableRecords([$questionWithMarkers, $questionWithoutMarkers])
        ->filterTable('has_visual_markers', true)
        ->assertCanSeeTableRecords([$questionWithMarkers])
        ->assertCanNotSeeTableRecords([$questionWithoutMarkers])
        ->filterTable('has_visual_markers', false)
        ->assertCanSeeTableRecords([$questionWithoutMarkers])
        ->assertCanNotSeeTableRecords([$questionWithMarkers]);
});

test('admin can filter questions by reference material in the questions list', function () {
    $admin = User::factory()->admin()->create();

    $questionWithReferenceMaterial = Question::factory()->create([
        'external_id' => 'REFERENCE-YES',
        'prompt' => 'Pytanie z materiałem referencyjnym',
    ]);

    $questionWithoutReferenceMaterial = Question::factory()->create([
        'external_id' => 'REFERENCE-NO',
        'prompt' => 'Pytanie bez materiału referencyjnego',
    ]);

    QuestionExplanationAsset::factory()
        ->for($questionWithReferenceMaterial, 'question')
        ->create([
            'position' => 1,
            'is_active' => true,
        ]);

    $this->actingAs($admin);

    Livewire::test(ListQuestions::class)
        ->assertTableFilterExists('has_reference_material')
        ->assertTableColumnExists('reference_explanation_asset_count')
        ->assertTableColumnStateSet('reference_explanation_asset_count', 1, $questionWithReferenceMaterial)
        ->assertTableColumnStateSet('reference_explanation_asset_count', 0, $questionWithoutReferenceMaterial)
        ->assertCanSeeTableRecords([$questionWithReferenceMaterial, $questionWithoutReferenceMaterial])
        ->filterTable('has_reference_material', true)
        ->assertCanSeeTableRecords([$questionWithReferenceMaterial])
        ->assertCanNotSeeTableRecords([$questionWithoutReferenceMaterial])
        ->filterTable('has_reference_material', false)
        ->assertCanSeeTableRecords([$questionWithoutReferenceMaterial])
        ->assertCanNotSeeTableRecords([$questionWithReferenceMaterial]);
});

test('admin search ignores inline formatting markers in question prompt', function () {
    $admin = User::factory()->admin()->create();

    $questionWithFormatting = Question::factory()->create([
        'external_id' => 'FORMAT-YES',
        'prompt' => 'Czy w przedstawionej [green]**sytuacji**[/green] jesteś ostrzegany o [red]kilku[/red] niebezpiecznych zakrętach w lewo?',
    ]);

    $otherQuestion = Question::factory()->create([
        'external_id' => 'FORMAT-NO',
        'prompt' => 'Pytanie kontrolne bez tego zestawu słów.',
    ]);

    $this->actingAs($admin);

    Livewire::test(ListQuestions::class)
        ->set('tableSearch', 'Czy w przedstawionej sytuacji jesteś ostrzegany o kilku niebezpiecznych zakrętach w lewo?')
        ->assertCanSeeTableRecords([$questionWithFormatting])
        ->assertCanNotSeeTableRecords([$otherQuestion]);
});
