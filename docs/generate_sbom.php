#!/usr/bin/env php
<?php
/**
 * S-BOM Generator for Voting System
 * Generates CycloneDX 1.5 format SBOM for vendor (backend) and frontend dependencies
 */

function generateVendorSBOM() {
    $composerLockFile = dirname(__DIR__) . '/composer.lock';
    $composerJsonFile = dirname(__DIR__) . '/composer.json';
    
    if (!file_exists($composerLockFile)) {
        die("Error: composer.lock not found.\n");
    }
    
    $composerLock = json_decode(file_get_contents($composerLockFile), true);
    $composerJson = file_exists($composerJsonFile) ? json_decode(file_get_contents($composerJsonFile), true) : [];
    
    $components = [];
    
    // Process packages from composer.lock
    foreach (['packages', 'packages-dev'] as $packageType) {
        if (!isset($composerLock[$packageType])) continue;
        
        foreach ($composerLock[$packageType] as $package) {
            $version = ltrim($package['version'], 'v');
            $component = [
                'type' => 'library',
                'bom-ref' => 'pkg:composer/' . $package['name'] . '@' . $version,
                'name' => $package['name'],
                'version' => $version,
                'purl' => 'pkg:composer/' . $package['name'] . '@' . $version,
            ];
            
            if (isset($package['description'])) {
                $component['description'] = $package['description'];
            }
            
            if (isset($package['license'])) {
                $licenses = is_array($package['license']) ? $package['license'] : [$package['license']];
                $component['licenses'] = array_map(function($license) {
                    return ['license' => ['id' => $license]];
                }, $licenses);
            }
            
            if (isset($package['source']['url'])) {
                $component['externalReferences'] = [
                    [
                        'type' => 'vcs',
                        'url' => $package['source']['url']
                    ]
                ];
            }
            
            $components[] = $component;
        }
    }
    
    $sbom = [
        'bomFormat' => 'CycloneDX',
        'specVersion' => '1.5',
        'serialNumber' => 'urn:uuid:' . generateUUID(),
        'version' => 1,
        'metadata' => [
            'timestamp' => date('c'),
            'component' => [
                'type' => 'application',
                'bom-ref' => 'pkg:composer/voting-system@1.0.0',
                'name' => $composerJson['name'] ?? 'voting-system',
                'version' => '1.0.0',
                'description' => $composerJson['description'] ?? 'Voting System Backend'
            ],
            'tools' => [
                [
                    'vendor' => 'Custom',
                    'name' => 'SBOM Generator',
                    'version' => '1.0.0'
                ]
            ]
        ],
        'components' => $components
    ];
    
    return $sbom;
}

function generateFrontendSBOM() {
    $components = [];
    $frontendDir = dirname(__DIR__) . '/frontend';
    
    if (!is_dir($frontendDir)) {
        return null;
    }
    
    // 只掃 frontend/<套件>/package.json，略過巢狀 packages/example
    $packageJsonFiles = glob($frontendDir . '/*/package.json') ?: [];
    
    // Process each package.json
    $processedPackages = [];
    foreach ($packageJsonFiles as $packageJsonFile) {
        $content = file_get_contents($packageJsonFile);
        $packageData = json_decode($content, true);
        if (!$packageData || !isset($packageData['name'])) continue;
        
        $name = $packageData['name'];
        $version = $packageData['version'] ?? 'unknown';
        
        // Skip duplicates
        $key = $name . '@' . $version;
        if (isset($processedPackages[$key])) continue;
        $processedPackages[$key] = true;
        
        $component = [
            'type' => 'library',
            'bom-ref' => 'pkg:npm/' . $name . '@' . $version,
            'name' => $name,
            'version' => $version,
            'purl' => 'pkg:npm/' . $name . '@' . $version,
        ];
        
        if (isset($packageData['description'])) {
            $component['description'] = $packageData['description'];
        }
        
        if (isset($packageData['license'])) {
            $licenses = is_array($packageData['license']) ? $packageData['license'] : [$packageData['license']];
            $component['licenses'] = array_map(function($license) {
                if (is_string($license)) {
                    return ['license' => ['id' => $license]];
                }
                return ['license' => ['id' => $license['type'] ?? 'UNKNOWN']];
            }, $licenses);
        }
        
        if (isset($packageData['repository'])) {
            $repo = $packageData['repository'];
            $url = is_array($repo) ? ($repo['url'] ?? '') : $repo;
            if ($url) {
                $component['externalReferences'] = [
                    [
                        'type' => 'vcs',
                        'url' => $url
                    ]
                ];
            }
        }
        
        $components[] = $component;
    }
    
    $sbom = [
        'bomFormat' => 'CycloneDX',
        'specVersion' => '1.5',
        'serialNumber' => 'urn:uuid:' . generateUUID(),
        'version' => 1,
        'metadata' => [
            'timestamp' => date('c'),
            'component' => [
                'type' => 'application',
                'bom-ref' => 'pkg:generic/voting-system-frontend@1.0.0',
                'name' => 'voting-system-frontend',
                'version' => '1.0.0',
                'description' => 'Voting System Frontend Dependencies'
            ],
            'tools' => [
                [
                    'vendor' => 'Custom',
                    'name' => 'SBOM Generator',
                    'version' => '1.0.0'
                ]
            ]
        ],
        'components' => $components
    ];
    
    return $sbom;
}

function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// Main execution
$type = $argv[1] ?? 'all';

switch ($type) {
    case 'vendor':
    case 'backend':
        echo "Generating vendor (backend) SBOM...\n";
        $sbom = generateVendorSBOM();
        $filename = 'sbom-vendor.json';
        file_put_contents($filename, json_encode($sbom, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo "✓ Generated: $filename (" . count($sbom['components']) . " components)\n";
        break;
        
    case 'frontend':
        echo "Generating frontend SBOM...\n";
        $sbom = generateFrontendSBOM();
        if ($sbom) {
            $filename = 'sbom-frontend.json';
            file_put_contents($filename, json_encode($sbom, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            echo "✓ Generated: $filename (" . count($sbom['components']) . " components)\n";
        } else {
            echo "✗ Frontend directory not found.\n";
        }
        break;
        
    case 'all':
    default:
        echo "Generating both vendor and frontend SBOMs...\n\n";
        
        $vendorSbom = generateVendorSBOM();
        file_put_contents('sbom-vendor.json', json_encode($vendorSbom, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo "✓ Generated: sbom-vendor.json (" . count($vendorSbom['components']) . " components)\n";
        
        $frontendSbom = generateFrontendSBOM();
        if ($frontendSbom) {
            file_put_contents('sbom-frontend.json', json_encode($frontendSbom, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            echo "✓ Generated: sbom-frontend.json (" . count($frontendSbom['components']) . " components)\n";
        } else {
            echo "✗ Frontend SBOM generation skipped (directory not found).\n";
        }
        
        echo "\nDone!\n";
        break;
}
