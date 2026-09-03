<?php

namespace App\Models;

use Database\Factories\QuestionLearningOrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionLearningOrderItem extends Model
{
    /** @use HasFactory<QuestionLearningOrderItemFactory> */
    use HasFactory;

    protected $fillable = [
        'question_learning_order_set_id',
        'question_id',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    public function orderSet(): BelongsTo
    {
        return $this->belongsTo(QuestionLearningOrderSet::class, 'question_learning_order_set_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
