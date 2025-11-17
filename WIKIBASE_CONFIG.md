# Wikibase Configuration for OGT API

This document outlines the environment configuration needed to switch between Wikidata and FactGrid as data sources.

## Environment Variables

Add the following to your `.env` file to configure the data source:

```env
# Wikibase Configuration
# Valid values: 'wikidata', 'factgrid'
EXPORT_TARGET=wikidata
```

## Wikidata Configuration

Default settings for Wikidata:

```env
WIKIDATA_URL=https://query.wikidata.org/sparql
WIKIDATA_API_URL=https://www.wikidata.org/w/api.php
WIKIDATA_CACHED_PERSONS_FILE=wikidata/persons.json
WIKIDATA_CACHED_PLACES_FILE=wikidata/places.json
```

## FactGrid Configuration

Required settings for FactGrid:

```env
FACTGRID_URL=https://database.factgrid.de/query
FACTGRID_API_URL=https://database.factgrid.de/api.php
FACTGRID_CACHED_PERSONS_FILE=factgrid/persons.json
FACTGRID_CACHED_PLACES_FILE=factgrid/places.json
```

## Switching Data Sources

To switch from Wikidata to FactGrid:

1. Set `EXPORT_TARGET=factgrid` in your `.env` file
2. Make sure all the required FactGrid environment variables are set
3. Restart the application to apply the changes

To switch back to Wikidata:

1. Set `EXPORT_TARGET=wikidata` in your `.env` file
2. Restart the application to apply the changes

# Frontend Configuration

To allow frontend components to be aware of the data source, add the following to your `.env` file:

```env
# Export this environment variable to the frontend JavaScript
MIX_EXPORT_TARGET=${EXPORT_TARGET}
```

Then make sure Laravel Mix compiles this variable by adding it to your `webpack.mix.js` file:

```javascript
mix.js('resources/js/app.js', 'public/js')
   .vue()
   .sass('resources/sass/app.scss', 'public/css')
   .options({
      processCssUrls: false,
   })
   .version();

// Make environment variables accessible to the frontend
if (process.env.MIX_EXPORT_TARGET) {
   console.log(`Building for data source: ${process.env.MIX_EXPORT_TARGET}`);
}
```

## API Endpoints

The API supports endpoints with both prefixes:

- `/api/wikidata/...` - These endpoints will work regardless of the `EXPORT_TARGET` setting
- `/api/factgrid/...` - These endpoints provide explicit access to FactGrid data

Example endpoints:
- `/api/wikidata/places/cache` - Will return data from either Wikidata or FactGrid based on configuration
- `/api/factgrid/places/cache` - Will explicitly return data from FactGrid regardless of configuration

## Implementation

The application automatically uses the configured data source from the backend:

1. The backend sets the data source based on the `EXPORT_TARGET` environment variable
2. This configuration is passed to the frontend via blade templates
3. The frontend uses this configuration to determine which API endpoints to call
4. Web workers also use the configured data source for background processing

To switch between data sources, you need to modify the `.env` file and restart the application.

## Storage Paths

When switching between data sources, separate cache files are maintained:

- Wikidata cache: `storage/app/wikidata/`
- FactGrid cache: `storage/app/factgrid/`

This ensures that data from different sources does not get mixed.