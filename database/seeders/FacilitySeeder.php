<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Facility;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FacilitySeeder extends Seeder
{
    private $facilityTypes = ['Classroom', 'Laboratory', 'Auditorium', 'Gymnasium', 'Conference Room', 'Library'];
    private $buildingNames = ['Main Building', 'Science Complex', 'Arts Center', 'Sports Facility', 'Student Center'];

    public function run(): void
    {
        $imagePath = database_path('seeders/facility_images');
        $files = glob($imagePath . '/*.{jpg,jpeg,png,gif}', GLOB_BRACE);

        foreach ($files as $file) {
            $this->createFacilityFromImage($file);
        }
    }

    private function createFacilityFromImage($imagePath)
    {
        $fileName = basename($imagePath);
        $destinationPath = 'facility-images/' . $fileName;

        // Copy image to public storage
        Storage::disk('public')->put($destinationPath, file_get_contents($imagePath));

        // Generate facility data
        $facilityName = $this->generateFacilityName();
        $facilityType = $this->facilityTypes[array_rand($this->facilityTypes)];
        $buildingName = $this->buildingNames[array_rand($this->buildingNames)];

        Facility::create([
            'facility_name' => $facilityName,
            'facility_type' => $facilityType,
            'capacity' => rand(20, 500),
            'building_name' => $buildingName,
            'floor_level' => rand(1, 5),
            'room_number' => $this->generateRoomNumber(),
            'description' => $this->generateDescription($facilityName, $facilityType),
            'facility_image' => $destinationPath,
            'status' => true,
        ]);
    }

    private function generateFacilityName()
    {
        $adjectives = ['Modern', 'Spacious', 'Advanced', 'Innovative', 'State-of-the-art'];
        $nouns = ['Hall', 'Room', 'Center', 'Lab', 'Studio'];

        return $adjectives[array_rand($adjectives)] . ' ' . $nouns[array_rand($nouns)] . ' ' . Str::random(3);
    }

    private function generateRoomNumber()
    {
        return strtoupper(Str::random(1)) . rand(100, 999);
    }

    private function generateDescription($name, $type)
    {
        return "The $name is a $type designed to accommodate various educational and extracurricular activities. " .
               "It provides a conducive environment for learning, collaboration, and academic excellence.";
    }
}