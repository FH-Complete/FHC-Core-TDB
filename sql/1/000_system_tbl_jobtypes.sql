INSERT INTO system.tbl_jobtypes (type, description) VALUES
('SZRGetBPKs', 'Get the BPKs from SZR'),
('TDBCreateFoerderfaelle', 'Create Foerderfaelle on TDB')
ON CONFLICT (type) DO NOTHING;

