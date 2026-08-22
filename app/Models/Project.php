<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'author',
        'institution',
        'author_email',
        'country',
        // The sequence counter beside this is deliberately NOT fillable: it is
        // internal state that only AccessionNumbers may advance.
        'accession_prefix',
        'finished',
        'published',
        'shared',
    ];

    /**
     * Mirrors the column default, so a project that has not been reloaded from
     * the database still reports the number it would issue next rather than 0.
     */
    protected $attributes = [
        'next_accession_number' => 1,
    ];

    protected $casts = [
        'next_accession_number' => 'integer',
        'finished' => 'boolean',
        'published' => 'boolean',
        'shared' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function accesses()
    {
        return $this->hasMany(ProjectAccess::class);
    }

    public function users()
    {
        $this->loadMissing('accesses.user');

        return $this->accesses->pluck('user');
    }

    public function invites()
    {
        return $this->hasMany(ProjectInvite::class);
    }

    public function interviewForms()
    {
        return $this->hasMany(InterviewForm::class);
    }

    public function specimens()
    {
        return $this->hasMany(Specimen::class);
    }

    public function activeInterviewForms()
    {
        return $this->hasMany(InterviewForm::class)->where('is_active', true);
    }

    public function catalogSpecies()
    {
        return $this->hasMany(CatalogSpecies::class);
    }

    /**
     * Query for answers to species-linkable items across all of this
     * project's forms. Returns a builder so callers can count() in SQL
     * instead of materializing every row.
     */
    public function speciesAnswers()
    {
        return InstanceAnswer::whereIn('interview_item_id', function ($query) {
            $query
                ->select('id')
                ->from('interview_items')
                ->where('link_to_species', true)
                ->whereIn('interview_section_id', function ($subquery) {
                    $subquery
                        ->select('id')
                        ->from('interview_sections')
                        ->whereIn(
                            'interview_form_id',
                            $this->interviewForms()->select('id')
                        );
                });
        });
    }

    public function unlinkedAnswers()
    {
        return $this->speciesAnswers()->whereNull('catalog_species_id');
    }

    public function linkedAnswers()
    {
        return $this->speciesAnswers()->whereNotNull('catalog_species_id');
    }

    public function linkedSpecies()
    {
        return CatalogSpecies::whereIn(
            'id',
            $this->linkedAnswers()->select('catalog_species_id')
        );
    }

    public function linkedFamilies()
    {
        return $this->linkedSpecies()
            ->pluck('family')
            ->filter()
            ->unique()
            ->values();
    }
}
