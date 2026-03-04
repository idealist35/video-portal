DELETE FROM videos
WHERE lower(coalesce(r2_key, '')) LIKE 'videos/heygen_%'
   OR lower(coalesce(r2_key, '')) = 'videos/obesity_alexis_captioned.mp4'
   OR lower(coalesce(r2_key, '')) = 'videos/rich_dad_final_20260302_captioned.mp4'
   OR lower(coalesce(r2_key, '')) = 'videos/20260303-161030-segment_09_grok_9x16_720p.mp4';

DELETE FROM videos
WHERE lower(trim(coalesce(title, ''))) = 'the blue zones'
  AND coalesce(r2_key, '') <> 'videos/The Blue Zones.mp4';

DELETE FROM videos
WHERE lower(trim(coalesce(title, ''))) = 'the daily stoic: 366 meditations on wisdom, perseverance, and the art of living'
  AND coalesce(r2_key, '') <> 'videos/The Daily Stoic: 366 Meditations on Wisdom, Perseverance, and the Art of Living.mp4';

DELETE FROM videos
WHERE lower(trim(coalesce(title, ''))) = 'dubai - the epicenter of modern innovation'
  AND coalesce(r2_key, '') <> 'videos/Dubai - The Epicenter of Modern Innovation.mp4';

DELETE FROM videos
WHERE lower(trim(coalesce(title, ''))) = 'the obesity code: unlocking the secrets of weight loss'
  AND coalesce(r2_key, '') <> 'videos/The Obesity Code: Unlocking the Secrets of Weight Loss.mp4';

DELETE FROM videos
WHERE lower(trim(coalesce(title, ''))) = 'rich dad, poor dad'
  AND coalesce(r2_key, '') <> 'videos/Rich Dad, Poor Dad.mp4';
