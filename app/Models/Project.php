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
        'is_example' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Forms, sections, interviews, records, permits and memberships go
        // with the project through their foreign keys. A form's questions and
        // the catalog have never had one, so without this a deleted project
        // leaves them behind. Gathered before the cascade takes the sections
        // that lead to the questions.
        static::deleting(function (Project $project) {
            InterviewItem::whereIn('interview_section_id', InterviewSection::select('interview_sections.id')
                ->join('interview_forms', 'interview_forms.id', '=', 'interview_sections.interview_form_id')
                ->where('interview_forms.project_id', $project->id))
                ->delete();

            $species = CatalogSpecies::where('project_id', $project->id);
            CatalogSpeciesPhoto::whereIn('catalog_species_id', (clone $species)->select('id'))->delete();
            $species->delete();
        });
    }

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

    public function fieldRecords()
    {
        return $this->hasMany(FieldRecord::class);
    }

    /** Every interview recorded in this project, across its forms. */
    public function interviewInstances()
    {
        return $this->hasManyThrough(
            InterviewInstance::class,
            InterviewForm::class,
            'project_id',
            'interview_form_id'
        );
    }

    /**
     * The project's photographs and recordings, an interview's or a field
     * record's alike.
     */
    public function media()
    {
        return Media::where(function ($query) {
            $query
                ->whereIn('interview_instance_id', $this->interviewInstances()->select('interview_instances.id'))
                ->orWhereIn('field_record_id', $this->fieldRecords()->select('id'));
        });
    }

    public function collectingPermits()
    {
        return $this->hasMany(CollectingPermit::class);
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
