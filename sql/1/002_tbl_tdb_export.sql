CREATE TABLE IF NOT EXISTS extension.tbl_tdb_export
(
    uebermittlung_id        varchar (256) not null,
    vorgangs_id INTEGER     not null
);

DO $$
BEGIN
ALTER TABLE ONLY extension.tbl_tdb_export ADD CONSTRAINT tbl_tdb_export_vorgangs_id_fkey FOREIGN KEY (vorgangs_id) REFERENCES public.tbl_konto(buchungsnr) ON DELETE RESTRICT ON UPDATE CASCADE;
EXCEPTION WHEN OTHERS THEN NULL;
END $$;

GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE extension.tbl_tdb_export TO vilesci;
