# AI VDB Provider FAISS

A Drupal module that provides a FAISS vector database provider for the AI VDB ecosystem.  
This module is based on the [ai_vdb_provider_opensearch](https://www.drupal.org/project/ai_vdb_provider_opensearch) module and adapts it to work with [FAISS](https://github.com/facebookresearch/faiss).

## Features

- Integrates FAISS as a vector database backend for Drupal.
- Works with [Search API](https://www.drupal.org/project/search_api) for flexible content indexing and querying.
- Designed for use with AI-powered search and retrieval applications.

## Getting Started

### 1. Clone the repository

```bash
git clone https://github.com/Dr-r123/ai_vdb_provider_faiss.git
```

### 2. Install the module

Copy the module into your Drupal installation under `web/modules/custom/` (or `modules/custom/` for non-composer setups).

Enable the module via the Drupal admin interface or Drush:

```bash
drush en ai_vdb_provider_faiss
```

### 3. Configure Search API

- Add a new Search API server and select "FAISS" as the backend.
- Configure the FAISS-specific settings as needed.

### 4. Index Content

- Create or update a Search API index to use the FAISS server.
- Index your content as usual.

### 5. Test Integration

- Use the [AI Search Block](https://www.drupal.org/project/ai_search_block) module to test vector search functionality.

## Contributing

Contributions, issues, and feature requests are welcome!  
Please open an issue or submit a pull request.

## License

MIT

---
