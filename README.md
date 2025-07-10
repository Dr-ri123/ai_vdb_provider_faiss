# FAISS Vector Database Provider

This module provides integration between Drupal's AI module and FAISS (Facebook AI Similarity Search) as a vector database (VDB) for storing and retrieving vector embeddings.

## Overview

FAISS is a library for efficient similarity search and clustering of dense vectors. This module allows Drupal to use FAISS as a vector database for:
- Storing vector embeddings from content
- Performing fast similarity searches
- Creating and managing vector collections locally

## Requirements

- Drupal 10+
- AI module
- Key module (for compatibility with AI module structure)

## Installation

1. **Enable the module**:
   ```bash
   drush en ai_vdb_provider_faiss
   ```

2. **Configure the module**:
   - Navigate to `/admin/config/search/ai/vdb-providers/faiss`
   - Set up your FAISS configuration settings