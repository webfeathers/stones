-- Add new Specimen Form options
UPDATE `custom_fields`
SET `options_json` = '["Raw","Slab Cut Raw","Slab Cut Polished","Tumbled","Crystal Point","Crystal Cluster","Carved Stone","Geode","Cut Gem","Cabochon","Worry Stone","Candle Holder","Book End"]'
WHERE `field_name` = 'specimen_form';
