# FAISS Vector Database Provider for Drupal

[![License: GPL-2.0-or-later](https://img.shields.io/badge/License-GPL%20v2+-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![Drupal](https://img.shields.io/badge/Drupal-10%2B-blue.svg)](https://www.drupal.org)
[![AI Module](https://img.shields.io/badge/AI%20Module-1.0%2B-green.svg)](https://www.drupal.org/project/ai)

This module provides integration between Drupal's AI module and FAISS (Facebook AI Similarity Search) as a vector database (VDB) for storing and retrieving vector embeddings.

## Overview

FAISS is a library for efficient similarity search and clustering of dense vectors. This module allows Drupal to use FAISS as a vector database for:

- **Storing vector embeddings** from content automatically
- **Performing fast similarity searches** across your content
- **Creating and managing vector collections** locally on your server
- **Supporting multiple index types** optimized for different use cases
- **Configurable distance metrics** (L2, Inner Product, Cosine Similarity)

## Features

- ✅ **Local Vector Storage**: No external dependencies or API keys required
- ✅ **Multiple Index Types**: Support for IndexFlatL2, IndexFlatIP, IndexIVFFlat, and IndexIVFPQ
- ✅ **Configurable Distance Metrics**: L2, Inner Product, and Cosine Similarity
- ✅ **Automatic Index Management**: Creates and manages FAISS indexes automatically
- ✅ **Drupal Integration**: Seamless integration with Drupal's AI module
- ✅ **Performance Optimized**: Efficient similarity search for large datasets

## Requirements

- **Drupal**: 10.2+ or 11.x
- **AI Module**: 1.0.0-beta1 or higher
- **Key Module**: 1.18 or higher (for compatibility with AI module structure)
- **PHP**: 8.1 or higher
- **Server**: Sufficient disk space for vector index storage

## Installation

### Method 1: Composer (Recommended)

```bash
composer require drupal/ai_vdb_provider_faiss
drush en ai_vdb_provider_faiss
```

### Method 2: Manual Installation

1. Download the module and place it in your `modules/custom/` directory
2. Enable the module:
   ```bash
   drush en ai_vdb_provider_faiss
   ```

## Configuration

### 1. Basic Configuration

Navigate to **Administration > Configuration > Search and metadata > AI > VDB Providers > FAISS** (`/admin/config/search/ai/vdb-providers/faiss`)

Configure the following settings:

- **Index Storage Path**: Directory where FAISS indexes will be stored (e.g., `sites/default/files/faiss_indexes`)
- **Index Type**: Choose the appropriate index type for your use case
- **Distance Metric**: Select the similarity metric
- **Number of Lists (nlist)**: For IVF indexes, set the number of clusters

### 2. Index Types Explained

| Index Type | Best For | Performance | Memory Usage |
|------------|----------|-------------|--------------|
| **IndexFlatL2** | Small datasets (< 1M vectors) | Exact results | High |
| **IndexFlatIP** | Small datasets with inner product | Exact results | High |
| **IndexIVFFlat** | Large datasets | Fast approximate | Medium |
| **IndexIVFPQ** | Very large datasets | Very fast approximate | Low |

### 3. Distance Metrics

- **L2 (Euclidean)**: Standard Euclidean distance, good for most use cases
- **Inner Product**: Dot product similarity, useful for normalized vectors
- **Cosine Similarity**: Angle-based similarity, ideal for text embeddings

## Usage

### Setting up Vector Collections

1. **Create a VDB Provider**: Go to AI module settings and create a new VDB provider using FAISS
2. **Configure Content Types**: Set up which content types should generate embeddings
3. **Generate Embeddings**: The AI module will automatically create vector embeddings for your content

### Example Configuration

```yaml
# Example configuration for a content site
index_path: 'sites/default/files/faiss_indexes'
index_type: 'IndexIVFFlat'  # Good for medium to large datasets
distance_metric: 'cosine'   # Good for text similarity
nlist: 1000                 # Adjust based on your dataset size
```

## Performance Considerations

### Index Type Selection

- **Small datasets (< 100K vectors)**: Use `IndexFlatL2` for exact results
- **Medium datasets (100K - 1M vectors)**: Use `IndexIVFFlat` for good balance
- **Large datasets (> 1M vectors)**: Use `IndexIVFPQ` for memory efficiency

### Storage Requirements

- **IndexFlatL2**: ~4 bytes per vector dimension
- **IndexIVFFlat**: ~4 bytes per vector dimension + overhead
- **IndexIVFPQ**: ~1 byte per vector dimension (with quantization)

### Example Storage Calculations

For 100,000 vectors with 768 dimensions:
- IndexFlatL2: ~300 MB
- IndexIVFFlat: ~350 MB
- IndexIVFPQ: ~100 MB

## Troubleshooting

### Common Issues

#### 1. Permission Errors
```
Error: Could not create the index directory
```
**Solution**: Ensure the web server has write permissions to the index directory:
```bash
chmod 755 sites/default/files/faiss_indexes
chown www-data:www-data sites/default/files/faiss_indexes
```

#### 2. Index Path Not Writable
```
Error: The index directory is not writable
```
**Solution**: Check directory permissions and ensure the path exists:
```bash
mkdir -p sites/default/files/faiss_indexes
chmod 755 sites/default/files/faiss_indexes
```

#### 3. Memory Issues with Large Datasets
**Solution**: 
- Use `IndexIVFPQ` for memory efficiency
- Increase PHP memory limit
- Consider using a dedicated server for vector operations

### Debugging

Enable Drupal's debug mode to see detailed error messages:
```php
// In settings.php
$config['system.logging']['error_level'] = 'verbose';
```

## Development

### Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

### Testing

```bash
# Run PHPUnit tests
./vendor/bin/phpunit modules/custom/ai_vdb_provider_faiss/tests

# Run coding standards
./vendor/bin/phpcs modules/custom/ai_vdb_provider_faiss
```

## API Reference

### FaissProvider Class

The main provider class that implements the VDB provider interface:

```php
use Drupal\ai_vdb_provider_faiss\Plugin\VdbProvider\FaissProvider;

// Get collections
$collections = $provider->getCollections('database_name');

// Create collection
$provider->createCollection('collection_name', 768, VdbSimilarityMetrics::CosineSimilarity);

// Vector search
$results = $provider->vectorSearch('collection_name', $vector, $fields, $query);
```

### Faiss Service

The core FAISS service for low-level operations:

```php
use Drupal\ai_vdb_provider_faiss\Faiss;

$faiss = \Drupal::service('faiss.api');
$faiss->setIndexPath('/path/to/indexes');
$faiss->createCollection('my_collection', 'db', 768, 'cosine');
```

## License

This project is licensed under the GNU General Public License v2.0 or later - see the [LICENSE](LICENSE) file for details.

## Support

- **Documentation**: [Drupal AI Module Documentation](https://www.drupal.org/docs/contributed-modules/ai)
- **Issues**: [Report bugs and feature requests](https://www.drupal.org/project/issues/ai_vdb_provider_faiss)
- **Community**: [Drupal AI Module Community](https://www.drupal.org/project/ai)

## Changelog

### 1.0.0-dev
- Initial release
- FAISS integration with Drupal AI module
- Support for multiple index types and distance metrics
- Local vector storage and management

## Acknowledgments

- [FAISS](https://github.com/facebookresearch/faiss) - Facebook AI Similarity Search
- [Drupal AI Module](https://www.drupal.org/project/ai) - Core AI functionality
- [Drupal Community](https://www.drupal.org) - For the amazing platform
