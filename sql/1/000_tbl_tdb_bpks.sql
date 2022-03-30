CREATE TABLE IF NOT EXISTS extension.tbl_tdb_bpks
(
    person_id        integer not null,
    vbPK_ZP_TD      varchar(256),
    vbPK_AS         varchar(256)
);

DO $$
    BEGIN
        ALTER TABLE ONLY extension.tbl_tdb_bpks ADD CONSTRAINT tbl_tdb_bpks_person_id_fkey FOREIGN KEY (person_id) REFERENCES public.tbl_person(person_id) ON DELETE RESTRICT ON UPDATE CASCADE;
    EXCEPTION WHEN OTHERS THEN NULL;
END $$;

GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE extension.tbl_tdb_bpks TO vilesci;
