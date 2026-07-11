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
        'finished',
        'published',
        'shared',
    ];

    protected $casts = [
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
        $users = collect();

        foreach ($this->accesses as $access) {
            $users->push($access->user);
        }

        return $users;
    }

    public function invites()
    {
        return $this->hasMany(ProjectInvite::class);
    }

    public function interviewForms()
    {
        return $this->hasMany(InterviewForm::class);
    }

    public function activeInterviewForms()
    {
        return $this->hasMany(InterviewForm::class)->where('is_active', true);
    }

    public function catalogSpecies()
    {
        return $this->hasMany(CatalogSpecies::class);
    }

    public function unlinkedAnswers()
    {
        return InstanceAnswer::whereNull('catalog_species_id')
            ->whereIn('interview_item_id', function ($query) {
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
                                $this->interviewForms()->pluck('id')
                            );
                    });
            })
            ->get();
    }

    public function linkedAnswers()
    {
        return InstanceAnswer::whereNotNull('catalog_species_id')
            ->whereIn('interview_item_id', function ($query) {
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
                                $this->interviewForms()->pluck('id')
                            );
                    });
            })
            ->get();
    }

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
                            $this->interviewForms()->pluck('id')
                        );
                });
        })
            ->get();
    }

    public function linkedSpecies()
    {
        return CatalogSpecies::whereIn(
            'id',
            $this->linkedAnswers()
                ->pluck('catalog_species_id')
                ->filter() // removes nulls
                ->unique()
        )->get();
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
