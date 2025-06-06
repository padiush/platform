<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\User;
use App\Models\ProjectAccess;
use App\Models\ProjectCapability;
use App\Models\ProjectInvite;
use App\Models\InterviewForm;
use App\Models\CatalogSpecies;
use App\Models\InstanceAnswer;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
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

    public function capabilities()
    {
        return $this->hasMany(ProjectCapability::class);
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
        $answers = collect();

        foreach ($this->interviewForms as $form) {
            $sections = $form->sections;
            foreach ($sections as $section) {
                foreach ($section->items as $item) {
                    if ($item->link_to_species) {
                        $item_answers = InstanceAnswer::where(
                            'interview_item_id',
                            $item->id
                        )->get();

                        foreach ($item_answers as $answer) {
                            if ($answer->catalog_species_id == null) {
                                $answers->push($answer);
                            }
                        }
                    }
                }
            }
        }

        return $answers;
    }

    public function linkedAnswers()
    {
        $answers = collect();

        foreach ($this->interviewForms as $form) {
            $sections = $form->sections;
            foreach ($sections as $section) {
                foreach ($section->items as $item) {
                    if ($item->link_to_species) {
                        $item_answers = InstanceAnswer::where(
                            'interview_item_id',
                            $item->id
                        )->get();

                        foreach ($item_answers as $answer) {
                            if ($answer->catalog_species_id != null) {
                                $answers->push($answer);
                            }
                        }
                    }
                }
            }
        }

        return $answers;
    }

    public function linkedSpecies()
    {
        $species = collect();

        foreach ($this->linkedAnswers() as $answer) {
            if (!$species->contains($answer->species)) {
                $species->push($answer->species);
            }
        }

        return $species;
    }

    public function linkedFamilies()
    {
        $families = collect();

        foreach ($this->linkedSpecies() as $species) {
            $this_species = CatalogSpecies::find($species->id);
            if (!$families->contains($this_species->family)) {
                $families->push($this_species->family);
            }
        }

        return $families;
    }
}
