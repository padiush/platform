<?php

namespace App\Imports;

use App\Models\CatalogSpecies;
use App\Models\Project;

use Maatwebsite\Excel\Concerns\ToModel;

class CatalogSpeciesImport implements ToModel
{
    
    /**
     * CatalogSpeciesImport constructor.
     *
     * @param Project $project_id The project ID to associate with the imported catalog species.
     */
    public function __construct(Project $project_id)
    {
        $this->project_id = $project_id;
    }

    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new CatalogSpecies([
            'project_id' => $this->project_id->id,
            'family' => $row[0],
            'genus' => $row[1],
            'name' => $row[2],
            'authority' => $row[3]
        ]);
    }
}
