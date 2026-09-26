--
-- PostgreSQL database dump
--

\restrict dummy123

-- Dumped from database version 18.6
-- Dumped by pg_dump version 18.6

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET transaction_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

ALTER TABLE IF EXISTS ONLY public.verval_realisasi_bosp DROP CONSTRAINT IF EXISTS verval_realisasi_bosp_profil_sekolah_id_foreign;
ALTER TABLE IF EXISTS ONLY public.verval_realisasi_bosp DROP CONSTRAINT IF EXISTS verval_realisasi_bosp_diverval_oleh_foreign;
ALTER TABLE IF EXISTS ONLY public.users DROP CONSTRAINT IF EXISTS users_profil_sekolah_id_foreign;
ALTER TABLE IF EXISTS ONLY public.surat_tpg DROP CONSTRAINT IF EXISTS surat_tpg_profil_sekolah_id_foreign;
ALTER TABLE IF EXISTS ONLY public.stock_opname_barang_persediaan DROP CONSTRAINT IF EXISTS stock_opname_barang_persediaan_rincian_belanja_barang_habis_pak;
ALTER TABLE IF EXISTS ONLY public.stock_opname_barang_persediaan DROP CONSTRAINT IF EXISTS stock_opname_barang_persediaan_profil_sekolah_id_foreign;
ALTER TABLE IF EXISTS ONLY public.stock_opname_barang_persediaan DROP CONSTRAINT IF EXISTS stock_opname_barang_persediaan_created_by_foreign;
ALTER TABLE IF EXISTS ONLY public.rincian_pemeliharaan DROP CONSTRAINT IF EXISTS rincian_pemeliharaan_profil_sekolah_id_foreign;
ALTER TABLE IF EXISTS ONLY public.rincian_pemeliharaan_pc DROP CONSTRAINT IF EXISTS rincian_pemeliharaan_pc_profil_sekolah_id_foreign;
ALTER TABLE IF EXISTS ONLY public.rincian_pemeliharaan_pc DROP CONSTRAINT IF EXISTS rincian_pemeliharaan_pc_created_by_foreign;
ALTER TABLE IF EXISTS ONLY public.rincian_pemeliharaan DROP CONSTRAINT IF EXISTS rincian_pemeliharaan_created_by_foreign;
ALTER TABLE IF EXISTS ONLY public.rincian_belanja_modal DROP CONSTRAINT IF EXISTS rincian_belanja_modal_profil_sekolah_id_foreign;
ALTER TABLE IF EXISTS ONLY public.rincian_belanja_modal DROP CONSTRAINT IF EXISTS rincian_belanja_modal_created_by_foreign;
ALTER TABLE IF EXISTS ONLY public.rincian_belanja_modal_bmd DROP CONSTRAINT IF EXISTS rincian_belanja_modal_bmd_profil_sekolah_id_foreign;
ALTER TABLE IF EXISTS ONLY public.rincian_belanja_modal_bmd DROP CONSTRAINT IF EXISTS rincian_belanja_modal_bmd_created_by_foreign;
ALTER TABLE IF EXISTS ONLY public.rincian_belanja_barang_habis_pakai DROP CONSTRAINT IF EXISTS rincian_belanja_barang_habis_pakai_profil_sekolah_id_foreign;
ALTER TABLE IF EXISTS ONLY public.rincian_belanja_barang_habis_pakai DROP CONSTRAINT IF EXISTS rincian_belanja_barang_habis_pakai_created_by_foreign;
ALTER TABLE IF EXISTS ONLY public.rekap_rkas DROP CONSTRAINT IF EXISTS rekap_rkas_profil_sekolah_id_foreign;
ALTER TABLE IF EXISTS ONLY public.rekap_rkas DROP CONSTRAINT IF EXISTS rekap_rkas_created_by_foreign;
ALTER TABLE IF EXISTS ONLY public.penerimaan_honor_ptk DROP CONSTRAINT IF EXISTS penerimaan_honor_ptk_profil_sekolah_id_foreign;
ALTER TABLE IF EXISTS ONLY public.penerimaan_honor_ptk DROP CONSTRAINT IF EXISTS penerimaan_honor_ptk_created_by_foreign;
ALTER TABLE IF EXISTS ONLY public.pendataan_ops DROP CONSTRAINT IF EXISTS pendataan_ops_profil_sekolah_id_foreign;
ALTER TABLE IF EXISTS ONLY public.pendataan_ops DROP CONSTRAINT IF EXISTS pendataan_ops_created_by_foreign;
ALTER TABLE IF EXISTS ONLY public.pendataan_bosp DROP CONSTRAINT IF EXISTS pendataan_bosp_profil_sekolah_id_foreign;
ALTER TABLE IF EXISTS ONLY public.pendataan_bosp DROP CONSTRAINT IF EXISTS pendataan_bosp_created_by_foreign;
ALTER TABLE IF EXISTS ONLY public.panduan_aplikasi_link DROP CONSTRAINT IF EXISTS panduan_aplikasi_link_panduan_aplikasi_id_foreign;
ALTER TABLE IF EXISTS ONLY public.panduan_aplikasi_file DROP CONSTRAINT IF EXISTS panduan_aplikasi_file_panduan_aplikasi_id_foreign;
ALTER TABLE IF EXISTS ONLY public.pajak_bosp_reguler DROP CONSTRAINT IF EXISTS pajak_bosp_reguler_profil_sekolah_id_foreign;
ALTER TABLE IF EXISTS ONLY public.pajak_bosp_reguler DROP CONSTRAINT IF EXISTS pajak_bosp_reguler_created_by_foreign;
ALTER TABLE IF EXISTS ONLY public.laporan_realisasi_bosp DROP CONSTRAINT IF EXISTS laporan_realisasi_bosp_profil_sekolah_id_foreign;
ALTER TABLE IF EXISTS ONLY public.laporan_realisasi_bosp DROP CONSTRAINT IF EXISTS laporan_realisasi_bosp_created_by_foreign;
ALTER TABLE IF EXISTS ONLY public.langganan_daya_jasa DROP CONSTRAINT IF EXISTS langganan_daya_jasa_profil_sekolah_id_foreign;
ALTER TABLE IF EXISTS ONLY public.langganan_daya_jasa DROP CONSTRAINT IF EXISTS langganan_daya_jasa_created_by_foreign;
ALTER TABLE IF EXISTS ONLY public.lampiran_2c DROP CONSTRAINT IF EXISTS lampiran_2c_profil_sekolah_id_foreign;
ALTER TABLE IF EXISTS ONLY public.lampiran_2c DROP CONSTRAINT IF EXISTS lampiran_2c_created_by_foreign;
ALTER TABLE IF EXISTS ONLY public.lampiran_2b DROP CONSTRAINT IF EXISTS lampiran_2b_profil_sekolah_id_foreign;
ALTER TABLE IF EXISTS ONLY public.lampiran_2b DROP CONSTRAINT IF EXISTS lampiran_2b_created_by_foreign;
ALTER TABLE IF EXISTS ONLY public.lampiran_2a DROP CONSTRAINT IF EXISTS lampiran_2a_profil_sekolah_id_foreign;
ALTER TABLE IF EXISTS ONLY public.lampiran_2a DROP CONSTRAINT IF EXISTS lampiran_2a_created_by_foreign;
ALTER TABLE IF EXISTS ONLY public.formulir_bos_k7 DROP CONSTRAINT IF EXISTS formulir_bos_k7_profil_sekolah_id_foreign;
ALTER TABLE IF EXISTS ONLY public.formulir_bos_k7 DROP CONSTRAINT IF EXISTS formulir_bos_k7_created_by_foreign;
ALTER TABLE IF EXISTS ONLY public.deadline_pekerjaan DROP CONSTRAINT IF EXISTS deadline_pekerjaan_diatur_oleh_foreign;
ALTER TABLE IF EXISTS ONLY public.dana_bosp_tahap DROP CONSTRAINT IF EXISTS dana_bosp_tahap_profil_sekolah_id_foreign;
ALTER TABLE IF EXISTS ONLY public.dana_bosp_tahap DROP CONSTRAINT IF EXISTS dana_bosp_tahap_created_by_foreign;
ALTER TABLE IF EXISTS ONLY public.biaya_pendaftaran_lomba DROP CONSTRAINT IF EXISTS biaya_pendaftaran_lomba_profil_sekolah_id_foreign;
ALTER TABLE IF EXISTS ONLY public.biaya_pendaftaran_lomba DROP CONSTRAINT IF EXISTS biaya_pendaftaran_lomba_created_by_foreign;
ALTER TABLE IF EXISTS ONLY public.belanja_honor_kegiatan DROP CONSTRAINT IF EXISTS belanja_honor_kegiatan_profil_sekolah_id_foreign;
ALTER TABLE IF EXISTS ONLY public.belanja_honor_kegiatan DROP CONSTRAINT IF EXISTS belanja_honor_kegiatan_created_by_foreign;
ALTER TABLE IF EXISTS ONLY public.backups DROP CONSTRAINT IF EXISTS backups_dibuat_oleh_id_foreign;
DROP INDEX IF EXISTS public.verval_realisasi_bosp_tahun_triwulan_index;
DROP INDEX IF EXISTS public.surat_tpg_tahun_triwulan_index;
DROP INDEX IF EXISTS public.stock_opname_barang_persediaan_tahun_triwulan_index;
DROP INDEX IF EXISTS public.sessions_user_id_index;
DROP INDEX IF EXISTS public.sessions_last_activity_index;
DROP INDEX IF EXISTS public.rincian_pemeliharaan_pc_jenis_tahun_triwulan_index;
DROP INDEX IF EXISTS public.rincian_pemeliharaan_jenis_tahun_triwulan_index;
DROP INDEX IF EXISTS public.rincian_belanja_modal_jenis_tahun_triwulan_index;
DROP INDEX IF EXISTS public.rincian_belanja_modal_bmd_tahun_triwulan_index;
DROP INDEX IF EXISTS public.rincian_belanja_barang_habis_pakai_tahun_triwulan_index;
DROP INDEX IF EXISTS public.penerimaan_honor_ptk_tahun_triwulan_index;
DROP INDEX IF EXISTS public.pajak_bosp_reguler_tahun_bulan_index;
DROP INDEX IF EXISTS public.laporan_realisasi_bosp_tahun_triwulan_index;
DROP INDEX IF EXISTS public.langganan_daya_jasa_tahun_triwulan_index;
DROP INDEX IF EXISTS public.lampiran_2c_profil_sekolah_id_triwulan_index;
DROP INDEX IF EXISTS public.lampiran_2c_profil_sekolah_id_index;
DROP INDEX IF EXISTS public.lampiran_2b_profil_sekolah_id_triwulan_index;
DROP INDEX IF EXISTS public.lampiran_2a_profil_sekolah_id_triwulan_index;
DROP INDEX IF EXISTS public.jobs_queue_index;
DROP INDEX IF EXISTS public.formulir_bos_k7_tahun_bulan_index;
DROP INDEX IF EXISTS public.failed_jobs_connection_queue_failed_at_index;
DROP INDEX IF EXISTS public.cache_locks_expiration_index;
DROP INDEX IF EXISTS public.cache_expiration_index;
DROP INDEX IF EXISTS public.biaya_pendaftaran_lomba_tahun_triwulan_index;
DROP INDEX IF EXISTS public.belanja_honor_kegiatan_jenis_tahun_triwulan_index;
ALTER TABLE IF EXISTS ONLY public.verval_realisasi_bosp DROP CONSTRAINT IF EXISTS verval_realisasi_bosp_profil_sekolah_id_tahun_triwulan_unique;
ALTER TABLE IF EXISTS ONLY public.verval_realisasi_bosp DROP CONSTRAINT IF EXISTS verval_realisasi_bosp_pkey;
ALTER TABLE IF EXISTS ONLY public.users DROP CONSTRAINT IF EXISTS users_username_unique;
ALTER TABLE IF EXISTS ONLY public.users DROP CONSTRAINT IF EXISTS users_pkey;
ALTER TABLE IF EXISTS ONLY public.surat_tpg DROP CONSTRAINT IF EXISTS surat_tpg_unik;
ALTER TABLE IF EXISTS ONLY public.surat_tpg DROP CONSTRAINT IF EXISTS surat_tpg_pkey;
ALTER TABLE IF EXISTS ONLY public.stock_opname_barang_persediaan DROP CONSTRAINT IF EXISTS stock_opname_rincian_id_unique;
ALTER TABLE IF EXISTS ONLY public.stock_opname_barang_persediaan DROP CONSTRAINT IF EXISTS stock_opname_barang_persediaan_pkey;
ALTER TABLE IF EXISTS ONLY public.sessions DROP CONSTRAINT IF EXISTS sessions_pkey;
ALTER TABLE IF EXISTS ONLY public.rincian_pemeliharaan DROP CONSTRAINT IF EXISTS rincian_pemeliharaan_pkey;
ALTER TABLE IF EXISTS ONLY public.rincian_pemeliharaan_pc DROP CONSTRAINT IF EXISTS rincian_pemeliharaan_pc_pkey;
ALTER TABLE IF EXISTS ONLY public.rincian_belanja_modal DROP CONSTRAINT IF EXISTS rincian_belanja_modal_pkey;
ALTER TABLE IF EXISTS ONLY public.rincian_belanja_modal_bmd DROP CONSTRAINT IF EXISTS rincian_belanja_modal_bmd_pkey;
ALTER TABLE IF EXISTS ONLY public.rincian_belanja_barang_habis_pakai DROP CONSTRAINT IF EXISTS rincian_belanja_barang_habis_pakai_pkey;
ALTER TABLE IF EXISTS ONLY public.rekap_rkas DROP CONSTRAINT IF EXISTS rekap_rkas_profil_sekolah_id_tahun_unique;
ALTER TABLE IF EXISTS ONLY public.rekap_rkas DROP CONSTRAINT IF EXISTS rekap_rkas_pkey;
ALTER TABLE IF EXISTS ONLY public.profil_sekolah DROP CONSTRAINT IF EXISTS profil_sekolah_pkey;
ALTER TABLE IF EXISTS ONLY public.profil_sekolah DROP CONSTRAINT IF EXISTS profil_sekolah_npsn_unique;
ALTER TABLE IF EXISTS ONLY public.pengaturan_tampilan DROP CONSTRAINT IF EXISTS pengaturan_tampilan_pkey;
ALTER TABLE IF EXISTS ONLY public.penerimaan_honor_ptk DROP CONSTRAINT IF EXISTS penerimaan_honor_ptk_profil_sekolah_id_tahun_triwulan_nuptk_uni;
ALTER TABLE IF EXISTS ONLY public.penerimaan_honor_ptk DROP CONSTRAINT IF EXISTS penerimaan_honor_ptk_pkey;
ALTER TABLE IF EXISTS ONLY public.pendataan_ops DROP CONSTRAINT IF EXISTS pendataan_ops_profil_sekolah_id_unique;
ALTER TABLE IF EXISTS ONLY public.pendataan_ops DROP CONSTRAINT IF EXISTS pendataan_ops_pkey;
ALTER TABLE IF EXISTS ONLY public.pendataan_bosp DROP CONSTRAINT IF EXISTS pendataan_bosp_profil_sekolah_id_unique;
ALTER TABLE IF EXISTS ONLY public.pendataan_bosp DROP CONSTRAINT IF EXISTS pendataan_bosp_pkey;
ALTER TABLE IF EXISTS ONLY public.panduan_aplikasi DROP CONSTRAINT IF EXISTS panduan_aplikasi_pkey;
ALTER TABLE IF EXISTS ONLY public.panduan_aplikasi_link DROP CONSTRAINT IF EXISTS panduan_aplikasi_link_pkey;
ALTER TABLE IF EXISTS ONLY public.panduan_aplikasi_file DROP CONSTRAINT IF EXISTS panduan_aplikasi_file_pkey;
ALTER TABLE IF EXISTS ONLY public.pajak_bosp_reguler DROP CONSTRAINT IF EXISTS pajak_bosp_reguler_profil_sekolah_id_tahun_bulan_unique;
ALTER TABLE IF EXISTS ONLY public.pajak_bosp_reguler DROP CONSTRAINT IF EXISTS pajak_bosp_reguler_pkey;
ALTER TABLE IF EXISTS ONLY public.migrations DROP CONSTRAINT IF EXISTS migrations_pkey;
ALTER TABLE IF EXISTS ONLY public.laporan_realisasi_bosp DROP CONSTRAINT IF EXISTS laporan_realisasi_bosp_profil_sekolah_id_tahun_triwulan_unique;
ALTER TABLE IF EXISTS ONLY public.laporan_realisasi_bosp DROP CONSTRAINT IF EXISTS laporan_realisasi_bosp_pkey;
ALTER TABLE IF EXISTS ONLY public.langganan_daya_jasa DROP CONSTRAINT IF EXISTS langganan_daya_jasa_pkey;
ALTER TABLE IF EXISTS ONLY public.lampiran_2c DROP CONSTRAINT IF EXISTS lampiran_2c_pkey;
ALTER TABLE IF EXISTS ONLY public.lampiran_2b DROP CONSTRAINT IF EXISTS lampiran_2b_pkey;
ALTER TABLE IF EXISTS ONLY public.lampiran_2a DROP CONSTRAINT IF EXISTS lampiran_2a_pkey;
ALTER TABLE IF EXISTS ONLY public.jobs DROP CONSTRAINT IF EXISTS jobs_pkey;
ALTER TABLE IF EXISTS ONLY public.job_batches DROP CONSTRAINT IF EXISTS job_batches_pkey;
ALTER TABLE IF EXISTS ONLY public.formulir_bos_k7 DROP CONSTRAINT IF EXISTS formulir_bos_k7_profil_sekolah_id_tahun_bulan_unique;
ALTER TABLE IF EXISTS ONLY public.formulir_bos_k7 DROP CONSTRAINT IF EXISTS formulir_bos_k7_pkey;
ALTER TABLE IF EXISTS ONLY public.failed_jobs DROP CONSTRAINT IF EXISTS failed_jobs_uuid_unique;
ALTER TABLE IF EXISTS ONLY public.failed_jobs DROP CONSTRAINT IF EXISTS failed_jobs_pkey;
ALTER TABLE IF EXISTS ONLY public.deadline_pekerjaan DROP CONSTRAINT IF EXISTS deadline_pekerjaan_tahun_kunci_menu_triwulan_unique;
ALTER TABLE IF EXISTS ONLY public.deadline_pekerjaan DROP CONSTRAINT IF EXISTS deadline_pekerjaan_pkey;
ALTER TABLE IF EXISTS ONLY public.dana_bosp_tahap DROP CONSTRAINT IF EXISTS dana_bosp_tahap_profil_sekolah_id_tahun_unique;
ALTER TABLE IF EXISTS ONLY public.dana_bosp_tahap DROP CONSTRAINT IF EXISTS dana_bosp_tahap_pkey;
ALTER TABLE IF EXISTS ONLY public.cache DROP CONSTRAINT IF EXISTS cache_pkey;
ALTER TABLE IF EXISTS ONLY public.cache_locks DROP CONSTRAINT IF EXISTS cache_locks_pkey;
ALTER TABLE IF EXISTS ONLY public.biaya_pendaftaran_lomba DROP CONSTRAINT IF EXISTS biaya_pendaftaran_lomba_pkey;
ALTER TABLE IF EXISTS ONLY public.belanja_honor_kegiatan DROP CONSTRAINT IF EXISTS belanja_honor_kegiatan_pkey;
ALTER TABLE IF EXISTS ONLY public.backups DROP CONSTRAINT IF EXISTS backups_pkey;
ALTER TABLE IF EXISTS public.verval_realisasi_bosp ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.users ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.surat_tpg ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.stock_opname_barang_persediaan ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.rincian_pemeliharaan_pc ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.rincian_pemeliharaan ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.rincian_belanja_modal_bmd ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.rincian_belanja_modal ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.rincian_belanja_barang_habis_pakai ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.rekap_rkas ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.profil_sekolah ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.pengaturan_tampilan ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.penerimaan_honor_ptk ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.pendataan_ops ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.pendataan_bosp ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.panduan_aplikasi_link ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.panduan_aplikasi_file ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.panduan_aplikasi ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.pajak_bosp_reguler ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.migrations ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.laporan_realisasi_bosp ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.langganan_daya_jasa ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.lampiran_2c ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.lampiran_2b ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.lampiran_2a ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.jobs ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.formulir_bos_k7 ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.failed_jobs ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.deadline_pekerjaan ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.dana_bosp_tahap ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.biaya_pendaftaran_lomba ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.belanja_honor_kegiatan ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.backups ALTER COLUMN id DROP DEFAULT;
DROP SEQUENCE IF EXISTS public.verval_realisasi_bosp_id_seq;
DROP TABLE IF EXISTS public.verval_realisasi_bosp;
DROP SEQUENCE IF EXISTS public.users_id_seq;
DROP TABLE IF EXISTS public.users;
DROP SEQUENCE IF EXISTS public.surat_tpg_id_seq;
DROP TABLE IF EXISTS public.surat_tpg;
DROP SEQUENCE IF EXISTS public.stock_opname_barang_persediaan_id_seq;
DROP TABLE IF EXISTS public.stock_opname_barang_persediaan;
DROP TABLE IF EXISTS public.sessions;
DROP SEQUENCE IF EXISTS public.rincian_pemeliharaan_pc_id_seq;
DROP TABLE IF EXISTS public.rincian_pemeliharaan_pc;
DROP SEQUENCE IF EXISTS public.rincian_pemeliharaan_id_seq;
DROP TABLE IF EXISTS public.rincian_pemeliharaan;
DROP SEQUENCE IF EXISTS public.rincian_belanja_modal_id_seq;
DROP SEQUENCE IF EXISTS public.rincian_belanja_modal_bmd_id_seq;
DROP TABLE IF EXISTS public.rincian_belanja_modal_bmd;
DROP TABLE IF EXISTS public.rincian_belanja_modal;
DROP SEQUENCE IF EXISTS public.rincian_belanja_barang_habis_pakai_id_seq;
DROP TABLE IF EXISTS public.rincian_belanja_barang_habis_pakai;
DROP SEQUENCE IF EXISTS public.rekap_rkas_id_seq;
DROP TABLE IF EXISTS public.rekap_rkas;
DROP SEQUENCE IF EXISTS public.profil_sekolah_id_seq;
DROP TABLE IF EXISTS public.profil_sekolah;
DROP SEQUENCE IF EXISTS public.pengaturan_tampilan_id_seq;
DROP TABLE IF EXISTS public.pengaturan_tampilan;
DROP SEQUENCE IF EXISTS public.penerimaan_honor_ptk_id_seq;
DROP TABLE IF EXISTS public.penerimaan_honor_ptk;
DROP SEQUENCE IF EXISTS public.pendataan_ops_id_seq;
DROP TABLE IF EXISTS public.pendataan_ops;
DROP SEQUENCE IF EXISTS public.pendataan_bosp_id_seq;
DROP TABLE IF EXISTS public.pendataan_bosp;
DROP SEQUENCE IF EXISTS public.panduan_aplikasi_link_id_seq;
DROP TABLE IF EXISTS public.panduan_aplikasi_link;
DROP SEQUENCE IF EXISTS public.panduan_aplikasi_id_seq;
DROP SEQUENCE IF EXISTS public.panduan_aplikasi_file_id_seq;
DROP TABLE IF EXISTS public.panduan_aplikasi_file;
DROP TABLE IF EXISTS public.panduan_aplikasi;
DROP SEQUENCE IF EXISTS public.pajak_bosp_reguler_id_seq;
DROP TABLE IF EXISTS public.pajak_bosp_reguler;
DROP SEQUENCE IF EXISTS public.migrations_id_seq;
DROP TABLE IF EXISTS public.migrations;
DROP SEQUENCE IF EXISTS public.laporan_realisasi_bosp_id_seq;
DROP TABLE IF EXISTS public.laporan_realisasi_bosp;
DROP SEQUENCE IF EXISTS public.langganan_daya_jasa_id_seq;
DROP TABLE IF EXISTS public.langganan_daya_jasa;
DROP SEQUENCE IF EXISTS public.lampiran_2c_id_seq;
DROP TABLE IF EXISTS public.lampiran_2c;
DROP SEQUENCE IF EXISTS public.lampiran_2b_id_seq;
DROP TABLE IF EXISTS public.lampiran_2b;
DROP SEQUENCE IF EXISTS public.lampiran_2a_id_seq;
DROP TABLE IF EXISTS public.lampiran_2a;
DROP SEQUENCE IF EXISTS public.jobs_id_seq;
DROP TABLE IF EXISTS public.jobs;
DROP TABLE IF EXISTS public.job_batches;
DROP SEQUENCE IF EXISTS public.formulir_bos_k7_id_seq;
DROP TABLE IF EXISTS public.formulir_bos_k7;
DROP SEQUENCE IF EXISTS public.failed_jobs_id_seq;
DROP TABLE IF EXISTS public.failed_jobs;
DROP SEQUENCE IF EXISTS public.deadline_pekerjaan_id_seq;
DROP TABLE IF EXISTS public.deadline_pekerjaan;
DROP SEQUENCE IF EXISTS public.dana_bosp_tahap_id_seq;
DROP TABLE IF EXISTS public.dana_bosp_tahap;
DROP TABLE IF EXISTS public.cache_locks;
DROP TABLE IF EXISTS public.cache;
DROP SEQUENCE IF EXISTS public.biaya_pendaftaran_lomba_id_seq;
DROP TABLE IF EXISTS public.biaya_pendaftaran_lomba;
DROP SEQUENCE IF EXISTS public.belanja_honor_kegiatan_id_seq;
DROP TABLE IF EXISTS public.belanja_honor_kegiatan;
DROP SEQUENCE IF EXISTS public.backups_id_seq;
DROP TABLE IF EXISTS public.backups;
SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: backups; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.backups (
    id bigint NOT NULL,
    tahun smallint NOT NULL,
    nama_file character varying(255) NOT NULL,
    path character varying(255) NOT NULL,
    ukuran bigint,
    dibuat_oleh_id bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.backups OWNER TO postgres;

--
-- Name: backups_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.backups_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.backups_id_seq OWNER TO postgres;

--
-- Name: backups_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.backups_id_seq OWNED BY public.backups.id;


--
-- Name: belanja_honor_kegiatan; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.belanja_honor_kegiatan (
    id bigint NOT NULL,
    profil_sekolah_id bigint NOT NULL,
    tahun smallint NOT NULL,
    triwulan smallint NOT NULL,
    uraian character varying(255),
    volume integer,
    satuan character varying(50),
    tarif_harga bigint,
    jumlah bigint,
    tanggal date,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    jenis character varying(255) DEFAULT 'honor_kegiatan'::character varying NOT NULL,
    CONSTRAINT belanja_honor_kegiatan_jenis_check CHECK (((jenis)::text = ANY ((ARRAY['honor_kegiatan'::character varying, 'makan_minum'::character varying, 'perjalanan_dinas'::character varying])::text[])))
);


ALTER TABLE public.belanja_honor_kegiatan OWNER TO postgres;

--
-- Name: belanja_honor_kegiatan_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.belanja_honor_kegiatan_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.belanja_honor_kegiatan_id_seq OWNER TO postgres;

--
-- Name: belanja_honor_kegiatan_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.belanja_honor_kegiatan_id_seq OWNED BY public.belanja_honor_kegiatan.id;


--
-- Name: biaya_pendaftaran_lomba; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.biaya_pendaftaran_lomba (
    id bigint NOT NULL,
    profil_sekolah_id bigint NOT NULL,
    tahun smallint NOT NULL,
    triwulan smallint NOT NULL,
    uraian character varying(255),
    volume integer,
    satuan character varying(50),
    tarif_harga bigint,
    jumlah bigint,
    tanggal date,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.biaya_pendaftaran_lomba OWNER TO postgres;

--
-- Name: biaya_pendaftaran_lomba_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.biaya_pendaftaran_lomba_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.biaya_pendaftaran_lomba_id_seq OWNER TO postgres;

--
-- Name: biaya_pendaftaran_lomba_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.biaya_pendaftaran_lomba_id_seq OWNED BY public.biaya_pendaftaran_lomba.id;


--
-- Name: cache; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration bigint NOT NULL
);


ALTER TABLE public.cache OWNER TO postgres;

--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration bigint NOT NULL
);


ALTER TABLE public.cache_locks OWNER TO postgres;

--
-- Name: dana_bosp_tahap; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.dana_bosp_tahap (
    id bigint NOT NULL,
    profil_sekolah_id bigint NOT NULL,
    tahun smallint NOT NULL,
    saldo_bosp_tahun_sebelumnya bigint,
    jumlah_siswa integer,
    jumlah_dana_bosp_per_tahun bigint,
    total_penerimaan_setahun bigint,
    penerimaan_tahap_1 bigint,
    penerimaan_tahap_2 bigint,
    saldo_bosp_tw4_tahun_sebelumnya bigint,
    tarik_tunai_tw1 bigint,
    tarik_tunai_tw2 bigint,
    tarik_tunai_tw3 bigint,
    tarik_tunai_tw4 bigint,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    saldo_kas_bank_tw1 bigint,
    saldo_kas_tunai_tw1 bigint,
    saldo_tw1 bigint,
    saldo_kas_bank_tw2 bigint,
    saldo_kas_tunai_tw2 bigint,
    saldo_tw2 bigint,
    saldo_kas_bank_tw3 bigint,
    saldo_kas_tunai_tw3 bigint,
    saldo_tw3 bigint,
    saldo_kas_bank_tw4 bigint,
    saldo_kas_tunai_tw4 bigint,
    saldo_tw4 bigint
);


ALTER TABLE public.dana_bosp_tahap OWNER TO postgres;

--
-- Name: dana_bosp_tahap_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.dana_bosp_tahap_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.dana_bosp_tahap_id_seq OWNER TO postgres;

--
-- Name: dana_bosp_tahap_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.dana_bosp_tahap_id_seq OWNED BY public.dana_bosp_tahap.id;


--
-- Name: deadline_pekerjaan; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.deadline_pekerjaan (
    id bigint NOT NULL,
    tahun smallint NOT NULL,
    kunci_menu character varying(255) NOT NULL,
    tanggal_deadline date NOT NULL,
    diatur_oleh bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    triwulan smallint NOT NULL
);


ALTER TABLE public.deadline_pekerjaan OWNER TO postgres;

--
-- Name: deadline_pekerjaan_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.deadline_pekerjaan_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.deadline_pekerjaan_id_seq OWNER TO postgres;

--
-- Name: deadline_pekerjaan_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.deadline_pekerjaan_id_seq OWNED BY public.deadline_pekerjaan.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection character varying(255) NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.failed_jobs OWNER TO postgres;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.failed_jobs_id_seq OWNER TO postgres;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: formulir_bos_k7; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.formulir_bos_k7 (
    id bigint NOT NULL,
    profil_sekolah_id bigint NOT NULL,
    tahun smallint NOT NULL,
    bulan smallint NOT NULL,
    lembar_100000 integer DEFAULT 0 NOT NULL,
    lembar_50000 integer DEFAULT 0 NOT NULL,
    lembar_20000 integer DEFAULT 0 NOT NULL,
    lembar_10000 integer DEFAULT 0 NOT NULL,
    lembar_5000 integer DEFAULT 0 NOT NULL,
    lembar_2000 integer DEFAULT 0 NOT NULL,
    lembar_1000 integer DEFAULT 0 NOT NULL,
    keping_1000 integer DEFAULT 0 NOT NULL,
    keping_500 integer DEFAULT 0 NOT NULL,
    keping_200 integer DEFAULT 0 NOT NULL,
    keping_100 integer DEFAULT 0 NOT NULL,
    saldo_rekening_bank bigint DEFAULT '0'::bigint NOT NULL,
    jumlah_total_penerimaan_bku bigint DEFAULT '0'::bigint NOT NULL,
    jumlah_total_pengeluaran_bku bigint DEFAULT '0'::bigint NOT NULL,
    penjelasan_perbedaan text,
    no_sk_kepala_sekolah character varying(255),
    tanggal_sk_kepala_sekolah date,
    no_sk_bendahara character varying(255),
    tanggal_sk_bendahara date,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    saldo_kas_tunai_manual bigint DEFAULT '0'::bigint NOT NULL
);


ALTER TABLE public.formulir_bos_k7 OWNER TO postgres;

--
-- Name: formulir_bos_k7_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.formulir_bos_k7_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.formulir_bos_k7_id_seq OWNER TO postgres;

--
-- Name: formulir_bos_k7_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.formulir_bos_k7_id_seq OWNED BY public.formulir_bos_k7.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


ALTER TABLE public.job_batches OWNER TO postgres;

--
-- Name: jobs; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


ALTER TABLE public.jobs OWNER TO postgres;

--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.jobs_id_seq OWNER TO postgres;

--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: lampiran_2a; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.lampiran_2a (
    id bigint NOT NULL,
    profil_sekolah_id bigint NOT NULL,
    triwulan smallint NOT NULL,
    tahun smallint NOT NULL,
    nrg character varying(12) NOT NULL,
    nuptk character varying(16) NOT NULL,
    nama_ptk character varying(255) NOT NULL,
    status_kepegawaian character varying(255) NOT NULL,
    gaji_pokok_januari bigint NOT NULL,
    npwp character varying(16) NOT NULL,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.lampiran_2a OWNER TO postgres;

--
-- Name: lampiran_2a_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.lampiran_2a_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.lampiran_2a_id_seq OWNER TO postgres;

--
-- Name: lampiran_2a_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.lampiran_2a_id_seq OWNED BY public.lampiran_2a.id;


--
-- Name: lampiran_2b; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.lampiran_2b (
    id bigint NOT NULL,
    profil_sekolah_id bigint NOT NULL,
    triwulan smallint NOT NULL,
    nrg character varying(12) NOT NULL,
    nuptk character varying(16) NOT NULL,
    nama_ptk character varying(255) NOT NULL,
    keterangan text NOT NULL,
    tmt date NOT NULL,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    tahun smallint
);


ALTER TABLE public.lampiran_2b OWNER TO postgres;

--
-- Name: lampiran_2b_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.lampiran_2b_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.lampiran_2b_id_seq OWNER TO postgres;

--
-- Name: lampiran_2b_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.lampiran_2b_id_seq OWNED BY public.lampiran_2b.id;


--
-- Name: lampiran_2c; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.lampiran_2c (
    id bigint NOT NULL,
    profil_sekolah_id bigint NOT NULL,
    nrg character varying(12) NOT NULL,
    nuptk character varying(16) NOT NULL,
    nama_ptk character varying(255) NOT NULL,
    kecamatan character varying(255) NOT NULL,
    jenis_kepangkatan character varying(255) NOT NULL,
    golongan character varying(255) NOT NULL,
    masa_kerja character varying(255) NOT NULL,
    pangkat_berkala character varying(255) NOT NULL,
    tmt date NOT NULL,
    gaji_pokok_lama bigint NOT NULL,
    gaji_pokok_baru bigint NOT NULL,
    keterangan text NOT NULL,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    triwulan smallint DEFAULT '1'::smallint NOT NULL,
    tahun smallint
);


ALTER TABLE public.lampiran_2c OWNER TO postgres;

--
-- Name: lampiran_2c_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.lampiran_2c_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.lampiran_2c_id_seq OWNER TO postgres;

--
-- Name: lampiran_2c_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.lampiran_2c_id_seq OWNED BY public.lampiran_2c.id;


--
-- Name: langganan_daya_jasa; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.langganan_daya_jasa (
    id bigint NOT NULL,
    profil_sekolah_id bigint NOT NULL,
    tahun smallint NOT NULL,
    triwulan smallint NOT NULL,
    uraian_pembayaran character varying(255),
    volume integer,
    satuan character varying(50),
    tarif_harga bigint,
    jumlah bigint,
    tanggal_bayar date,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.langganan_daya_jasa OWNER TO postgres;

--
-- Name: langganan_daya_jasa_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.langganan_daya_jasa_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.langganan_daya_jasa_id_seq OWNER TO postgres;

--
-- Name: langganan_daya_jasa_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.langganan_daya_jasa_id_seq OWNED BY public.langganan_daya_jasa.id;


--
-- Name: laporan_realisasi_bosp; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.laporan_realisasi_bosp (
    id bigint NOT NULL,
    profil_sekolah_id bigint NOT NULL,
    tahun smallint NOT NULL,
    triwulan smallint NOT NULL,
    saldo_awal_dana_bosp bigint,
    penerimaan_dana_bos bigint,
    total_penerimaan bigint,
    belanja_barang_pakai_habis_persediaan bigint,
    jasa_tenaga_pendidik_dan_kependidikan bigint,
    daya_dan_jasa bigint,
    pemeliharaan bigint,
    upah_pemeliharaan bigint,
    biaya_pendaftaran_lomba_bimtek_workshop bigint,
    honor_kegiatan bigint,
    makan_dan_minum_kegiatan bigint,
    perjalanan_dinas bigint,
    total_belanja_barang_dan_jasa bigint,
    peralatan_dan_mesin_kib_b bigint,
    aset_tetap_lainnya_kib_e bigint,
    total_belanja_modal bigint,
    total_realisasi_dana_bos bigint,
    sisa_dana_bos bigint,
    saldo_rekening_kas_bank bigint,
    saldo_kas_tunai bigint,
    verifikasi_jumlah bigint,
    verifikasi_saldo character varying(255),
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.laporan_realisasi_bosp OWNER TO postgres;

--
-- Name: laporan_realisasi_bosp_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.laporan_realisasi_bosp_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.laporan_realisasi_bosp_id_seq OWNER TO postgres;

--
-- Name: laporan_realisasi_bosp_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.laporan_realisasi_bosp_id_seq OWNED BY public.laporan_realisasi_bosp.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.migrations_id_seq OWNER TO postgres;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: pajak_bosp_reguler; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.pajak_bosp_reguler (
    id bigint NOT NULL,
    profil_sekolah_id bigint NOT NULL,
    tahun smallint NOT NULL,
    bulan smallint NOT NULL,
    ppn_debit bigint,
    pph21_debit bigint,
    pph23_debit bigint,
    pph4_debit bigint,
    sspd_debit bigint,
    ppn_kredit bigint,
    pph21_kredit bigint,
    pph23_kredit bigint,
    pph4_kredit bigint,
    sspd_kredit bigint,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.pajak_bosp_reguler OWNER TO postgres;

--
-- Name: pajak_bosp_reguler_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.pajak_bosp_reguler_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.pajak_bosp_reguler_id_seq OWNER TO postgres;

--
-- Name: pajak_bosp_reguler_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.pajak_bosp_reguler_id_seq OWNED BY public.pajak_bosp_reguler.id;


--
-- Name: panduan_aplikasi; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.panduan_aplikasi (
    id bigint NOT NULL,
    judul character varying(255) NOT NULL,
    deskripsi text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.panduan_aplikasi OWNER TO postgres;

--
-- Name: panduan_aplikasi_file; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.panduan_aplikasi_file (
    id bigint NOT NULL,
    panduan_aplikasi_id bigint NOT NULL,
    file_path character varying(255) NOT NULL,
    file_nama_asli character varying(255),
    file_ukuran bigint,
    file_mime character varying(255),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.panduan_aplikasi_file OWNER TO postgres;

--
-- Name: panduan_aplikasi_file_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.panduan_aplikasi_file_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.panduan_aplikasi_file_id_seq OWNER TO postgres;

--
-- Name: panduan_aplikasi_file_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.panduan_aplikasi_file_id_seq OWNED BY public.panduan_aplikasi_file.id;


--
-- Name: panduan_aplikasi_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.panduan_aplikasi_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.panduan_aplikasi_id_seq OWNER TO postgres;

--
-- Name: panduan_aplikasi_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.panduan_aplikasi_id_seq OWNED BY public.panduan_aplikasi.id;


--
-- Name: panduan_aplikasi_link; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.panduan_aplikasi_link (
    id bigint NOT NULL,
    panduan_aplikasi_id bigint NOT NULL,
    link_drive character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.panduan_aplikasi_link OWNER TO postgres;

--
-- Name: panduan_aplikasi_link_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.panduan_aplikasi_link_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.panduan_aplikasi_link_id_seq OWNER TO postgres;

--
-- Name: panduan_aplikasi_link_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.panduan_aplikasi_link_id_seq OWNED BY public.panduan_aplikasi_link.id;


--
-- Name: pendataan_bosp; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.pendataan_bosp (
    id bigint NOT NULL,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    profil_sekolah_id bigint,
    nuptk character varying(255),
    nama character varying(255),
    nip character varying(255),
    jk character varying(1),
    tempat_lahir character varying(255),
    tanggal_lahir date,
    status_kepegawaian character varying(255),
    pendidikan_terakhir character varying(255),
    jurusan character varying(255),
    nama_perguruan_tinggi character varying(255),
    no_whatsapp character varying(12)
);


ALTER TABLE public.pendataan_bosp OWNER TO postgres;

--
-- Name: pendataan_bosp_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.pendataan_bosp_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.pendataan_bosp_id_seq OWNER TO postgres;

--
-- Name: pendataan_bosp_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.pendataan_bosp_id_seq OWNED BY public.pendataan_bosp.id;


--
-- Name: pendataan_ops; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.pendataan_ops (
    id bigint NOT NULL,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    profil_sekolah_id bigint,
    nuptk character varying(255),
    nama character varying(255),
    nip character varying(255),
    jk character varying(1),
    tempat_lahir character varying(255),
    tanggal_lahir date,
    status_kepegawaian character varying(255),
    pendidikan_terakhir character varying(255),
    jurusan character varying(255),
    nama_perguruan_tinggi character varying(255),
    no_whatsapp character varying(12),
    foto_ops character varying(255),
    sk_ops character varying(255)
);


ALTER TABLE public.pendataan_ops OWNER TO postgres;

--
-- Name: pendataan_ops_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.pendataan_ops_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.pendataan_ops_id_seq OWNER TO postgres;

--
-- Name: pendataan_ops_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.pendataan_ops_id_seq OWNED BY public.pendataan_ops.id;


--
-- Name: penerimaan_honor_ptk; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.penerimaan_honor_ptk (
    id bigint NOT NULL,
    profil_sekolah_id bigint NOT NULL,
    tahun smallint NOT NULL,
    triwulan smallint NOT NULL,
    nuptk character varying(16),
    nama_penerima character varying(255),
    volume integer,
    satuan character varying(50),
    tarif_harga bigint,
    jumlah_honor bigint,
    tanggal_bayar date,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.penerimaan_honor_ptk OWNER TO postgres;

--
-- Name: penerimaan_honor_ptk_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.penerimaan_honor_ptk_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.penerimaan_honor_ptk_id_seq OWNER TO postgres;

--
-- Name: penerimaan_honor_ptk_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.penerimaan_honor_ptk_id_seq OWNED BY public.penerimaan_honor_ptk.id;


--
-- Name: pengaturan_tampilan; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.pengaturan_tampilan (
    id bigint NOT NULL,
    background_landing character varying(255),
    background_login character varying(255),
    warna_halaman character varying(20) DEFAULT '#f1f5f9'::character varying NOT NULL,
    warna_menu character varying(20) DEFAULT '#0f172a'::character varying NOT NULL,
    ukuran_huruf_halaman character varying(10) DEFAULT 'sedang'::character varying NOT NULL,
    ukuran_huruf_menu character varying(10) DEFAULT 'sedang'::character varying NOT NULL,
    jenis_huruf_halaman character varying(40) DEFAULT 'Figtree'::character varying NOT NULL,
    jenis_huruf_menu character varying(40) DEFAULT 'Figtree'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    warna_huruf_menu character varying(255) DEFAULT '#cbd5e1'::character varying NOT NULL,
    warna_huruf_landing character varying(255) DEFAULT '#334155'::character varying NOT NULL,
    warna_huruf_login character varying(255) DEFAULT '#334155'::character varying NOT NULL,
    warna_huruf_registrasi character varying(255) DEFAULT '#334155'::character varying NOT NULL,
    ukuran_huruf_registrasi character varying(255) DEFAULT 'sedang'::character varying NOT NULL,
    jenis_huruf_registrasi character varying(255) DEFAULT 'Figtree'::character varying NOT NULL
);


ALTER TABLE public.pengaturan_tampilan OWNER TO postgres;

--
-- Name: pengaturan_tampilan_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.pengaturan_tampilan_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.pengaturan_tampilan_id_seq OWNER TO postgres;

--
-- Name: pengaturan_tampilan_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.pengaturan_tampilan_id_seq OWNED BY public.pengaturan_tampilan.id;


--
-- Name: profil_sekolah; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.profil_sekolah (
    id bigint NOT NULL,
    npsn character varying(255),
    nama_sekolah character varying(255) NOT NULL,
    nama_kepala_sekolah character varying(255),
    nip_kepala_sekolah character varying(255),
    alamat_sekolah text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    status character varying(255),
    kecamatan character varying(255),
    status_kepegawaian_kepsek character varying(255),
    nama_bendahara character varying(255),
    nip_bendahara character varying(255),
    status_kepegawaian_bendahara character varying(255),
    nama_pengawas character varying(255),
    nip_pengawas character varying(255),
    no_whatsapp_kepala_sekolah character varying(255),
    kode_upb character varying(255),
    subrayon character varying(255),
    kop_surat character varying(255)
);


ALTER TABLE public.profil_sekolah OWNER TO postgres;

--
-- Name: profil_sekolah_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.profil_sekolah_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.profil_sekolah_id_seq OWNER TO postgres;

--
-- Name: profil_sekolah_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.profil_sekolah_id_seq OWNED BY public.profil_sekolah.id;


--
-- Name: rekap_rkas; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.rekap_rkas (
    id bigint NOT NULL,
    profil_sekolah_id bigint NOT NULL,
    tahun smallint NOT NULL,
    anggaran_bosp bigint,
    pegawai_sebelum bigint,
    pegawai_realisasi_tahap1 bigint,
    pegawai_perubahan_tahap2 bigint,
    pegawai_jml_sesudah bigint,
    pegawai_selisih bigint,
    pemeliharaan_sebelum bigint,
    pemeliharaan_realisasi_tahap1 bigint,
    pemeliharaan_perubahan_tahap2 bigint,
    pemeliharaan_jml_sesudah bigint,
    pemeliharaan_selisih bigint,
    barjas_sebelum bigint,
    barjas_realisasi_tahap1 bigint,
    barjas_perubahan_tahap2 bigint,
    barjas_jml_sesudah bigint,
    barjas_selisih bigint,
    peralatan_mesin_sebelum bigint,
    peralatan_mesin_realisasi_tahap1 bigint,
    peralatan_mesin_perubahan_tahap2 bigint,
    peralatan_mesin_jml_sesudah bigint,
    peralatan_mesin_selisih bigint,
    aset_lainnya_sebelum bigint,
    aset_lainnya_realisasi_tahap1 bigint,
    aset_lainnya_perubahan_tahap2 bigint,
    aset_lainnya_jml_sesudah bigint,
    aset_lainnya_selisih bigint,
    jumlah_sebelum bigint,
    jumlah_sesudah bigint,
    jumlah_selisih bigint,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.rekap_rkas OWNER TO postgres;

--
-- Name: rekap_rkas_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.rekap_rkas_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.rekap_rkas_id_seq OWNER TO postgres;

--
-- Name: rekap_rkas_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.rekap_rkas_id_seq OWNED BY public.rekap_rkas.id;


--
-- Name: rincian_belanja_barang_habis_pakai; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.rincian_belanja_barang_habis_pakai (
    id bigint NOT NULL,
    profil_sekolah_id bigint NOT NULL,
    tahun smallint NOT NULL,
    triwulan smallint NOT NULL,
    kode_upb character varying(255),
    nama_barang character varying(255),
    nama_merk_barang character varying(255),
    volume integer,
    satuan character varying(50),
    harga_satuan bigint,
    total_harga bigint,
    asal_usul character varying(255),
    tanggal date,
    keterangan text,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.rincian_belanja_barang_habis_pakai OWNER TO postgres;

--
-- Name: rincian_belanja_barang_habis_pakai_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.rincian_belanja_barang_habis_pakai_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.rincian_belanja_barang_habis_pakai_id_seq OWNER TO postgres;

--
-- Name: rincian_belanja_barang_habis_pakai_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.rincian_belanja_barang_habis_pakai_id_seq OWNED BY public.rincian_belanja_barang_habis_pakai.id;


--
-- Name: rincian_belanja_modal; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.rincian_belanja_modal (
    id bigint NOT NULL,
    profil_sekolah_id bigint NOT NULL,
    jenis character varying(255) NOT NULL,
    tahun smallint NOT NULL,
    triwulan smallint NOT NULL,
    kode_upb character varying(255),
    nama_barang character varying(255),
    nama_merk_barang character varying(255),
    volume integer,
    satuan character varying(50),
    harga_satuan bigint,
    total_harga bigint,
    asal_usul character varying(255),
    tanggal date,
    keterangan text,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT rincian_belanja_modal_jenis_check CHECK (((jenis)::text = ANY ((ARRAY['peralatan_mesin'::character varying, 'aset_tetap_lainnya'::character varying])::text[])))
);


ALTER TABLE public.rincian_belanja_modal OWNER TO postgres;

--
-- Name: rincian_belanja_modal_bmd; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.rincian_belanja_modal_bmd (
    id bigint NOT NULL,
    profil_sekolah_id bigint NOT NULL,
    tahun smallint NOT NULL,
    triwulan smallint NOT NULL,
    bentuk_kontrak character varying(255),
    atribusi character varying(255),
    jumlah_termin character varying(255),
    ppk character varying(255),
    nomor_dokumen character varying(255),
    tanggal_perolehan date,
    penyedia character varying(255),
    kode_belanja character varying(255),
    rekening_belanja character varying(255),
    jenis_aset character varying(255),
    sub_sub_rincian_objek character varying(255),
    jumlah integer,
    satuan character varying(50),
    harga_satuan bigint,
    total bigint,
    no_bast character varying(255),
    tanggal_bast date,
    keterangan_bos character varying(255),
    nomor_surat_pernyataan character varying(255),
    tanggal_surat_pernyataan date,
    nama_pengurus_barang character varying(255),
    jabatan character varying(255),
    pejabat_penata_usaha character varying(255),
    nama_barang character varying(255),
    spesifikasi_nama_barang character varying(255),
    spesifikasi_lain character varying(255),
    merk_pengarang character varying(255),
    keterangan_bosp character varying(255),
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.rincian_belanja_modal_bmd OWNER TO postgres;

--
-- Name: rincian_belanja_modal_bmd_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.rincian_belanja_modal_bmd_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.rincian_belanja_modal_bmd_id_seq OWNER TO postgres;

--
-- Name: rincian_belanja_modal_bmd_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.rincian_belanja_modal_bmd_id_seq OWNED BY public.rincian_belanja_modal_bmd.id;


--
-- Name: rincian_belanja_modal_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.rincian_belanja_modal_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.rincian_belanja_modal_id_seq OWNER TO postgres;

--
-- Name: rincian_belanja_modal_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.rincian_belanja_modal_id_seq OWNED BY public.rincian_belanja_modal.id;


--
-- Name: rincian_pemeliharaan; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.rincian_pemeliharaan (
    id bigint NOT NULL,
    profil_sekolah_id bigint NOT NULL,
    jenis character varying(255) NOT NULL,
    tahun smallint NOT NULL,
    triwulan smallint NOT NULL,
    kode_upb character varying(255),
    nama_barang character varying(255),
    nama_merk_barang character varying(255),
    volume integer,
    satuan character varying(50),
    harga_satuan bigint,
    total_harga bigint,
    asal_usul character varying(255),
    tanggal date,
    keterangan text,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT rincian_pemeliharaan_jenis_check CHECK (((jenis)::text = ANY ((ARRAY['barang'::character varying, 'jasa'::character varying])::text[])))
);


ALTER TABLE public.rincian_pemeliharaan OWNER TO postgres;

--
-- Name: rincian_pemeliharaan_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.rincian_pemeliharaan_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.rincian_pemeliharaan_id_seq OWNER TO postgres;

--
-- Name: rincian_pemeliharaan_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.rincian_pemeliharaan_id_seq OWNED BY public.rincian_pemeliharaan.id;


--
-- Name: rincian_pemeliharaan_pc; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.rincian_pemeliharaan_pc (
    id bigint NOT NULL,
    profil_sekolah_id bigint NOT NULL,
    jenis character varying(255) NOT NULL,
    tahun smallint NOT NULL,
    triwulan smallint NOT NULL,
    kode_upb character varying(255),
    nama_barang character varying(255),
    nama_merk_barang character varying(255),
    volume integer,
    satuan character varying(50),
    harga_satuan bigint,
    total_harga bigint,
    asal_usul character varying(255),
    tanggal date,
    keterangan text,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT rincian_pemeliharaan_pc_jenis_check CHECK (((jenis)::text = ANY ((ARRAY['barang'::character varying, 'jasa'::character varying])::text[])))
);


ALTER TABLE public.rincian_pemeliharaan_pc OWNER TO postgres;

--
-- Name: rincian_pemeliharaan_pc_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.rincian_pemeliharaan_pc_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.rincian_pemeliharaan_pc_id_seq OWNER TO postgres;

--
-- Name: rincian_pemeliharaan_pc_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.rincian_pemeliharaan_pc_id_seq OWNED BY public.rincian_pemeliharaan_pc.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


ALTER TABLE public.sessions OWNER TO postgres;

--
-- Name: stock_opname_barang_persediaan; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.stock_opname_barang_persediaan (
    id bigint NOT NULL,
    profil_sekolah_id bigint NOT NULL,
    tahun smallint NOT NULL,
    triwulan smallint NOT NULL,
    nama_barang character varying(255),
    satuan character varying(50),
    harga bigint,
    saldo_awal_kuantitas integer,
    saldo_awal_jumlah bigint,
    penerimaan_kuantitas integer,
    penerimaan_jumlah bigint,
    pengeluaran_kuantitas integer,
    pengeluaran_jumlah bigint,
    saldo_akhir_kuantitas integer,
    saldo_akhir_jumlah bigint,
    keterangan text,
    created_by bigint,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    rincian_belanja_barang_habis_pakai_id bigint
);


ALTER TABLE public.stock_opname_barang_persediaan OWNER TO postgres;

--
-- Name: stock_opname_barang_persediaan_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.stock_opname_barang_persediaan_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.stock_opname_barang_persediaan_id_seq OWNER TO postgres;

--
-- Name: stock_opname_barang_persediaan_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.stock_opname_barang_persediaan_id_seq OWNED BY public.stock_opname_barang_persediaan.id;


--
-- Name: surat_tpg; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.surat_tpg (
    id bigint NOT NULL,
    profil_sekolah_id bigint NOT NULL,
    tahun smallint NOT NULL,
    triwulan smallint NOT NULL,
    jenis character varying(255) NOT NULL,
    nomor_surat character varying(255),
    tanggal_surat date,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    tahun_pelajaran character varying(255)
);


ALTER TABLE public.surat_tpg OWNER TO postgres;

--
-- Name: surat_tpg_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.surat_tpg_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.surat_tpg_id_seq OWNER TO postgres;

--
-- Name: surat_tpg_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.surat_tpg_id_seq OWNED BY public.surat_tpg.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    username character varying(255) NOT NULL,
    password character varying(255) NOT NULL,
    level_akses character varying(255) NOT NULL,
    nama_sekolah character varying(255),
    jabatan character varying(255),
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    profil_sekolah_id bigint,
    must_change_password boolean DEFAULT false NOT NULL,
    is_approved boolean DEFAULT true NOT NULL
);


ALTER TABLE public.users OWNER TO postgres;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO postgres;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: verval_realisasi_bosp; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.verval_realisasi_bosp (
    id bigint NOT NULL,
    profil_sekolah_id bigint NOT NULL,
    tahun smallint NOT NULL,
    triwulan smallint NOT NULL,
    status character varying(255),
    diverval_oleh bigint,
    diverval_pada timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.verval_realisasi_bosp OWNER TO postgres;

--
-- Name: verval_realisasi_bosp_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.verval_realisasi_bosp_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.verval_realisasi_bosp_id_seq OWNER TO postgres;

--
-- Name: verval_realisasi_bosp_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.verval_realisasi_bosp_id_seq OWNED BY public.verval_realisasi_bosp.id;


--
-- Name: backups id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.backups ALTER COLUMN id SET DEFAULT nextval('public.backups_id_seq'::regclass);


--
-- Name: belanja_honor_kegiatan id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.belanja_honor_kegiatan ALTER COLUMN id SET DEFAULT nextval('public.belanja_honor_kegiatan_id_seq'::regclass);


--
-- Name: biaya_pendaftaran_lomba id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.biaya_pendaftaran_lomba ALTER COLUMN id SET DEFAULT nextval('public.biaya_pendaftaran_lomba_id_seq'::regclass);


--
-- Name: dana_bosp_tahap id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.dana_bosp_tahap ALTER COLUMN id SET DEFAULT nextval('public.dana_bosp_tahap_id_seq'::regclass);


--
-- Name: deadline_pekerjaan id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.deadline_pekerjaan ALTER COLUMN id SET DEFAULT nextval('public.deadline_pekerjaan_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: formulir_bos_k7 id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.formulir_bos_k7 ALTER COLUMN id SET DEFAULT nextval('public.formulir_bos_k7_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: lampiran_2a id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.lampiran_2a ALTER COLUMN id SET DEFAULT nextval('public.lampiran_2a_id_seq'::regclass);


--
-- Name: lampiran_2b id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.lampiran_2b ALTER COLUMN id SET DEFAULT nextval('public.lampiran_2b_id_seq'::regclass);


--
-- Name: lampiran_2c id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.lampiran_2c ALTER COLUMN id SET DEFAULT nextval('public.lampiran_2c_id_seq'::regclass);


--
-- Name: langganan_daya_jasa id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.langganan_daya_jasa ALTER COLUMN id SET DEFAULT nextval('public.langganan_daya_jasa_id_seq'::regclass);


--
-- Name: laporan_realisasi_bosp id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.laporan_realisasi_bosp ALTER COLUMN id SET DEFAULT nextval('public.laporan_realisasi_bosp_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: pajak_bosp_reguler id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pajak_bosp_reguler ALTER COLUMN id SET DEFAULT nextval('public.pajak_bosp_reguler_id_seq'::regclass);


--
-- Name: panduan_aplikasi id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.panduan_aplikasi ALTER COLUMN id SET DEFAULT nextval('public.panduan_aplikasi_id_seq'::regclass);


--
-- Name: panduan_aplikasi_file id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.panduan_aplikasi_file ALTER COLUMN id SET DEFAULT nextval('public.panduan_aplikasi_file_id_seq'::regclass);


--
-- Name: panduan_aplikasi_link id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.panduan_aplikasi_link ALTER COLUMN id SET DEFAULT nextval('public.panduan_aplikasi_link_id_seq'::regclass);


--
-- Name: pendataan_bosp id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pendataan_bosp ALTER COLUMN id SET DEFAULT nextval('public.pendataan_bosp_id_seq'::regclass);


--
-- Name: pendataan_ops id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pendataan_ops ALTER COLUMN id SET DEFAULT nextval('public.pendataan_ops_id_seq'::regclass);


--
-- Name: penerimaan_honor_ptk id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.penerimaan_honor_ptk ALTER COLUMN id SET DEFAULT nextval('public.penerimaan_honor_ptk_id_seq'::regclass);


--
-- Name: pengaturan_tampilan id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pengaturan_tampilan ALTER COLUMN id SET DEFAULT nextval('public.pengaturan_tampilan_id_seq'::regclass);


--
-- Name: profil_sekolah id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.profil_sekolah ALTER COLUMN id SET DEFAULT nextval('public.profil_sekolah_id_seq'::regclass);


--
-- Name: rekap_rkas id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rekap_rkas ALTER COLUMN id SET DEFAULT nextval('public.rekap_rkas_id_seq'::regclass);


--
-- Name: rincian_belanja_barang_habis_pakai id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rincian_belanja_barang_habis_pakai ALTER COLUMN id SET DEFAULT nextval('public.rincian_belanja_barang_habis_pakai_id_seq'::regclass);


--
-- Name: rincian_belanja_modal id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rincian_belanja_modal ALTER COLUMN id SET DEFAULT nextval('public.rincian_belanja_modal_id_seq'::regclass);


--
-- Name: rincian_belanja_modal_bmd id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rincian_belanja_modal_bmd ALTER COLUMN id SET DEFAULT nextval('public.rincian_belanja_modal_bmd_id_seq'::regclass);


--
-- Name: rincian_pemeliharaan id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rincian_pemeliharaan ALTER COLUMN id SET DEFAULT nextval('public.rincian_pemeliharaan_id_seq'::regclass);


--
-- Name: rincian_pemeliharaan_pc id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rincian_pemeliharaan_pc ALTER COLUMN id SET DEFAULT nextval('public.rincian_pemeliharaan_pc_id_seq'::regclass);


--
-- Name: stock_opname_barang_persediaan id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.stock_opname_barang_persediaan ALTER COLUMN id SET DEFAULT nextval('public.stock_opname_barang_persediaan_id_seq'::regclass);


--
-- Name: surat_tpg id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.surat_tpg ALTER COLUMN id SET DEFAULT nextval('public.surat_tpg_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: verval_realisasi_bosp id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.verval_realisasi_bosp ALTER COLUMN id SET DEFAULT nextval('public.verval_realisasi_bosp_id_seq'::regclass);


--
-- Data for Name: backups; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.backups (id, tahun, nama_file, path, ukuran, dibuat_oleh_id, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: belanja_honor_kegiatan; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.belanja_honor_kegiatan (id, profil_sekolah_id, tahun, triwulan, uraian, volume, satuan, tarif_harga, jumlah, tanggal, created_by, created_at, updated_at, jenis) FROM stdin;
\.


--
-- Data for Name: biaya_pendaftaran_lomba; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.biaya_pendaftaran_lomba (id, profil_sekolah_id, tahun, triwulan, uraian, volume, satuan, tarif_harga, jumlah, tanggal, created_by, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cache (key, value, expiration) FROM stdin;
ops-bosp-sr-cbd-cache-pengaturan-tampilan	a:17:{s:2:"id";i:1;s:18:"background_landing";s:53:"tampilan/0Ik4oNWwSfoPArMMDlEz9CARXdrZZ2KfoKoN0GBB.png";s:16:"background_login";s:39:"tampilan/bg_6a9b8961a91862.95219547.jpg";s:13:"warna_halaman";s:7:"#000000";s:10:"warna_menu";s:7:"#121212";s:20:"ukuran_huruf_halaman";s:6:"sedang";s:17:"ukuran_huruf_menu";s:5:"kecil";s:19:"jenis_huruf_halaman";s:7:"Poppins";s:16:"jenis_huruf_menu";s:6:"Nunito";s:10:"created_at";s:19:"2026-09-03 21:35:38";s:10:"updated_at";s:19:"2026-09-05 06:26:00";s:16:"warna_huruf_menu";s:7:"#ffffff";s:19:"warna_huruf_landing";s:7:"#ffffff";s:17:"warna_huruf_login";s:7:"#ffffff";s:22:"warna_huruf_registrasi";s:7:"#f7f7f8";s:23:"ukuran_huruf_registrasi";s:6:"sedang";s:22:"jenis_huruf_registrasi";s:7:"Figtree";}	2105648976
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: dana_bosp_tahap; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.dana_bosp_tahap (id, profil_sekolah_id, tahun, saldo_bosp_tahun_sebelumnya, jumlah_siswa, jumlah_dana_bosp_per_tahun, total_penerimaan_setahun, penerimaan_tahap_1, penerimaan_tahap_2, saldo_bosp_tw4_tahun_sebelumnya, tarik_tunai_tw1, tarik_tunai_tw2, tarik_tunai_tw3, tarik_tunai_tw4, created_by, created_at, updated_at, saldo_kas_bank_tw1, saldo_kas_tunai_tw1, saldo_tw1, saldo_kas_bank_tw2, saldo_kas_tunai_tw2, saldo_tw2, saldo_kas_bank_tw3, saldo_kas_tunai_tw3, saldo_tw3, saldo_kas_bank_tw4, saldo_kas_tunai_tw4, saldo_tw4) FROM stdin;
2	4	2026	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	1	2026-09-16 23:41:25	2026-09-16 23:41:25	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
5	61	2026	\N	10	1100000	11000000	5500000	5500000	\N	5000000	\N	\N	\N	10	2026-09-23 02:30:41	2026-09-23 12:48:14	500000	4990000	5490000	\N	\N	\N	\N	\N	\N	\N	\N	\N
6	12	2026	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	1	2026-09-23 13:54:35	2026-09-23 13:54:35	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N
4	11	2026	\N	\N	\N	0	0	0	\N	\N	\N	\N	\N	1	2026-09-17 11:54:51	2026-09-23 13:54:55	\N	\N	0	\N	\N	0	\N	\N	0	\N	\N	\N
1	2	2026	\N	5	1100000	5500000	2750000	2750000	\N	\N	\N	\N	\N	1	2026-09-16 23:40:38	2026-09-24 01:59:50	\N	\N	0	\N	\N	0	\N	\N	0	\N	\N	0
3	58	2026	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	1	2026-09-17 00:28:33	2026-09-17 00:30:18	\N	\N	0	\N	\N	0	\N	\N	0	\N	\N	0
\.


--
-- Data for Name: deadline_pekerjaan; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.deadline_pekerjaan (id, tahun, kunci_menu, tanggal_deadline, diatur_oleh, created_at, updated_at, triwulan) FROM stdin;
\.


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- Data for Name: formulir_bos_k7; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.formulir_bos_k7 (id, profil_sekolah_id, tahun, bulan, lembar_100000, lembar_50000, lembar_20000, lembar_10000, lembar_5000, lembar_2000, lembar_1000, keping_1000, keping_500, keping_200, keping_100, saldo_rekening_bank, jumlah_total_penerimaan_bku, jumlah_total_pengeluaran_bku, penjelasan_perbedaan, no_sk_kepala_sekolah, tanggal_sk_kepala_sekolah, no_sk_bendahara, tanggal_sk_bendahara, created_by, created_at, updated_at, saldo_kas_tunai_manual) FROM stdin;
1	52	2026	12	10	0	0	0	0	0	0	0	0	0	0	0	0	0	\N	\N	\N	\N	\N	1	2026-09-23 03:25:11	2026-09-23 03:25:11	0
2	11	2026	6	0	0	0	0	0	0	0	0	0	0	0	0	100	0	\N	\N	\N	\N	\N	1	2026-09-23 03:32:56	2026-09-23 03:32:56	0
4	11	2026	9	23	0	0	0	0	0	1	0	0	0	1	346739300	394350000	45309650	Rp. 050, dibulatkan menjadi Rp. 100,- sehingga terdapat selisih dan perbedaan sebesar Rp. -50,-	824/Kep.1127-SEKRET/2021	2021-09-30	400.3.5.5/ 203 – SMPN/2024	2024-01-02	1	2026-09-23 03:35:55	2026-09-23 05:47:40	0
5	61	2026	9	0	0	0	0	0	0	0	0	0	0	0	0	0	0	\N	\N	\N	\N	\N	10	2026-09-23 12:49:26	2026-09-23 12:49:26	0
\.


--
-- Data for Name: job_batches; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.job_batches (id, name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options, cancelled_at, created_at, finished_at) FROM stdin;
\.


--
-- Data for Name: jobs; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.jobs (id, queue, payload, attempts, reserved_at, available_at, created_at) FROM stdin;
\.


--
-- Data for Name: lampiran_2a; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.lampiran_2a (id, profil_sekolah_id, triwulan, tahun, nrg, nuptk, nama_ptk, status_kepegawaian, gaji_pokok_januari, npwp, created_by, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: lampiran_2b; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.lampiran_2b (id, profil_sekolah_id, triwulan, nrg, nuptk, nama_ptk, keterangan, tmt, created_by, created_at, updated_at, tahun) FROM stdin;
\.


--
-- Data for Name: lampiran_2c; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.lampiran_2c (id, profil_sekolah_id, nrg, nuptk, nama_ptk, kecamatan, jenis_kepangkatan, golongan, masa_kerja, pangkat_berkala, tmt, gaji_pokok_lama, gaji_pokok_baru, keterangan, created_by, created_at, updated_at, triwulan, tahun) FROM stdin;
\.


--
-- Data for Name: langganan_daya_jasa; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.langganan_daya_jasa (id, profil_sekolah_id, tahun, triwulan, uraian_pembayaran, volume, satuan, tarif_harga, jumlah, tanggal_bayar, created_by, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: laporan_realisasi_bosp; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.laporan_realisasi_bosp (id, profil_sekolah_id, tahun, triwulan, saldo_awal_dana_bosp, penerimaan_dana_bos, total_penerimaan, belanja_barang_pakai_habis_persediaan, jasa_tenaga_pendidik_dan_kependidikan, daya_dan_jasa, pemeliharaan, upah_pemeliharaan, biaya_pendaftaran_lomba_bimtek_workshop, honor_kegiatan, makan_dan_minum_kegiatan, perjalanan_dinas, total_belanja_barang_dan_jasa, peralatan_dan_mesin_kib_b, aset_tetap_lainnya_kib_e, total_belanja_modal, total_realisasi_dana_bos, sisa_dana_bos, saldo_rekening_kas_bank, saldo_kas_tunai, verifikasi_jumlah, verifikasi_saldo, created_by, created_at, updated_at) FROM stdin;
1	2	2026	1	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	0	SAMA	1	2026-09-17 05:18:50	2026-09-17 09:07:28
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2026_09_03_084625_create_profil_sekolah_table	1
5	2026_09_03_084626_create_pendataan_ops_table	1
6	2026_09_03_084627_create_pendataan_bosp_table	1
7	2026_09_03_124722_add_status_kecamatan_and_npsn_unique_to_profil_sekolah_table	1
8	2026_09_03_124723_add_profil_sekolah_id_to_users_table	1
9	2026_09_03_130820_add_must_change_password_to_users_table	2
10	2026_09_03_133219_add_is_approved_to_users_table	3
11	2026_09_03_211847_create_pengaturan_tampilan_table	4
12	2026_09_03_215620_add_bendahara_and_status_kepegawaian_to_profil_sekolah_table	5
13	2026_09_03_215621_add_identitas_fields_to_pendataan_ops_table	5
14	2026_09_03_215622_add_identitas_fields_to_pendataan_bosp_table	5
15	2026_09_04_001137_create_lampiran_2a_table	6
16	2026_09_04_030000_create_lampiran_2b_table	7
17	2026_09_04_060000_create_lampiran_2c_table	8
18	2026_09_04_070000_add_triwulan_to_lampiran_2c_table	9
19	2026_09_04_090000_add_pengawas_to_profil_sekolah_table	10
20	2026_09_04_100000_remove_logo_from_profil_sekolah_table	11
21	2026_09_04_120000_add_foto_dan_sk_ops_to_pendataan_ops_table	12
22	2026_09_04_140000_add_no_whatsapp_kepala_sekolah_to_profil_sekolah_table	13
23	2026_09_05_010627_add_warna_huruf_to_pengaturan_tampilan_table	14
24	2026_09_05_022129_add_tahun_to_lampiran_2b_dan_2c_table	15
25	2026_09_05_090000_add_huruf_registrasi_to_pengaturan_tampilan_table	15
26	2026_09_09_130000_create_rekap_rkas_table	16
27	2026_09_09_214122_backfill_rumus_jml_sesudah_dan_selisih_rekap_rkas_table	17
28	2026_09_09_235920_create_penerimaan_honor_ptk_table	18
29	2026_09_10_041911_create_langganan_daya_jasa_table	19
30	2026_09_10_060512_create_rincian_pemeliharaan_table	20
31	2026_09_10_090000_create_rincian_pemeliharaan_pc_table	21
32	2026_09_11_060000_create_biaya_pendaftaran_lomba_table	22
33	2026_09_11_070000_create_belanja_honor_kegiatan_table	22
34	2026_09_11_090000_add_jenis_to_belanja_honor_kegiatan_table	23
35	2026_09_11_100000_create_rincian_belanja_modal_table	24
36	2026_09_11_110000_create_rincian_belanja_barang_habis_pakai_table	24
37	2026_09_11_120000_create_pajak_bosp_reguler_table	25
38	2026_09_16_130000_create_dana_bosp_tahap_table	26
39	2026_09_16_150000_add_saldo_tw_ke_dana_bosp_tahap_table	27
40	2026_09_17_090000_add_subrayon_dan_kode_upb_to_profil_sekolah_table	28
41	2026_09_17_100000_create_laporan_realisasi_bosp_table	28
42	2026_09_18_090000_create_stock_opname_barang_persediaan_table	29
43	2026_09_19_070000_add_rincian_link_dan_rumus_stock_opname	30
44	2026_09_22_220000_create_rincian_belanja_modal_bmd_table	31
45	2026_09_22_230000_backfill_ppk_otomatis_rincian_belanja_modal_bmd	32
46	2026_09_23_010000_backfill_anggaran_bosp_otomatis_rekap_rkas	33
47	2026_09_23_100000_create_formulir_bos_k7_table	34
48	2026_09_23_150000_add_saldo_kas_tunai_manual_ke_formulir_bos_k7	35
49	2026_09_23_200000_create_verval_realisasi_bosp_table	36
50	2026_09_23_210000_create_deadline_pekerjaan_table	37
51	2026_09_23_220000_add_triwulan_to_deadline_pekerjaan_table	38
52	2026_09_24_090000_create_surat_tpg_table	39
53	2026_09_24_150000_add_kop_surat_to_profil_sekolah_table	40
54	2026_09_24_160000_add_tahun_pelajaran_to_surat_tpg_table	41
55	2026_09_24_170000_create_panduan_aplikasi_table	42
56	2026_09_24_180000_restructure_panduan_aplikasi_untuk_multi_file_dan_link	43
57	2026_09_24_190000_create_backups_table	43
\.


--
-- Data for Name: pajak_bosp_reguler; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.pajak_bosp_reguler (id, profil_sekolah_id, tahun, bulan, ppn_debit, pph21_debit, pph23_debit, pph4_debit, sspd_debit, ppn_kredit, pph21_kredit, pph23_kredit, pph4_kredit, sspd_kredit, created_by, created_at, updated_at) FROM stdin;
1	52	2026	1	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	1	2026-09-15 21:25:33	2026-09-17 11:53:30
2	52	2026	2	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	1	2026-09-15 21:27:08	2026-09-17 11:54:07
\.


--
-- Data for Name: panduan_aplikasi; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.panduan_aplikasi (id, judul, deskripsi, created_at, updated_at) FROM stdin;
1	Data Aplikasi Awal	Data Aplkasi Awal	2026-09-24 12:51:05	2026-09-24 12:51:05
2	Data Aplikasi-update 1-7	Data Aplikasi-update 1-7	2026-09-24 12:52:26	2026-09-24 12:52:26
3	Data Aplikasi Update 8.0	Data Aplikasi Update 8.0	2026-09-24 12:53:07	2026-09-24 12:53:07
5	Data Aplikasi Update 10	Data Aplikasi Update 10	2026-09-24 12:54:42	2026-09-24 12:54:42
6	Data Aplikasi-Update 11	Data Aplikasi-Update 11	2026-09-24 12:55:29	2026-09-24 12:55:29
4	Data Aplikasi-Update-9.0-9.1 perbaikan-lampiran2b-export-tandatangan	Data Aplikasi-Update-9.0-9.1 perbaikan-lampiran2b-export-tandatangan	2026-09-24 12:53:59	2026-09-24 12:56:01
7	Data Aplikasi-Update 12-Bag 1	Data Aplikasi-Update 12.0-12.10-Bag 1	2026-09-24 13:04:58	2026-09-24 13:04:58
8	Data Aplikasi-Update 12-Bag 2	Data Aplikasi-Update 12.11-12.20-Bag 2	2026-09-24 13:05:36	2026-09-24 13:05:36
9	Data Aplikasi-Update 12-Bag 3	Data Aplikasi-Update 12.21-12.30-Bag 3	2026-09-24 13:06:28	2026-09-24 13:06:28
10	Data Aplikasi-update-13.31 saldo-tw-tarik-tunai	Data Aplikasi-update-13.31 saldo-tw-tarik-tunai	2026-09-24 13:07:06	2026-09-24 13:07:06
11	Data Aplikasi-update 14	Data Aplikasi-update 14	2026-09-24 13:07:48	2026-09-24 13:07:48
12	Data Aplikasi-update 15-Bag 1	Data Aplikasi-update 15-Bag 1	2026-09-24 13:09:27	2026-09-24 13:09:27
13	Data Aplikasi-update 15-Bag 2	Data Aplikasi-update 15-Bag 2	2026-09-24 13:10:23	2026-09-24 13:10:23
14	Data Aplikasi-update 15-Bag 3	Data Aplikasi-update 15-Bag 3	2026-09-24 13:10:49	2026-09-24 13:10:49
\.


--
-- Data for Name: panduan_aplikasi_file; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.panduan_aplikasi_file (id, panduan_aplikasi_id, file_path, file_nama_asli, file_ukuran, file_mime, created_at, updated_at) FROM stdin;
1	1	panduan-aplikasi/LzEPuYBCOeF5GD6VC002H096B4QumYvrEldW9JUz.zip	srcbd-ops-bosp-source-Awal.zip	148545	application/octet-stream	2026-09-24 12:51:05	2026-09-24 12:51:05
2	2	panduan-aplikasi/bl9RaBWgsIbu3RmNP1a0N70s7rzWRMwYZxgxqak6.zip	ui-redesign-update1.zip	34904	application/octet-stream	2026-09-24 12:52:26	2026-09-24 12:52:26
3	2	panduan-aplikasi/AqzDMdmzwr1IA8QnkohnKUP410evKZRagDejTeRa.zip	update-2.zip	48415	application/octet-stream	2026-09-24 12:52:26	2026-09-24 12:52:26
4	2	panduan-aplikasi/GcpxdL0Vnuq5N5B6jqCj7wK8Qqj58tAs0lrIJjuU.zip	update-3.zip	54676	application/octet-stream	2026-09-24 12:52:26	2026-09-24 12:52:26
5	2	panduan-aplikasi/kJwHN0SZdDl18CU9FOLBvj3IFl9d2urxhgBQ6Ptl.zip	update-4.zip	59853	application/octet-stream	2026-09-24 12:52:26	2026-09-24 12:52:26
6	2	panduan-aplikasi/p9DxkiYQxanoGzwYilK38VBlLSIohn2ZyM6IkrpY.zip	update-5.zip	63323	application/octet-stream	2026-09-24 12:52:26	2026-09-24 12:52:26
7	2	panduan-aplikasi/W7Xhk6DdRjP5W4BTeGo3tMGemuAjcZtV0JWJHEYo.zip	update-6.zip	74143	application/octet-stream	2026-09-24 12:52:26	2026-09-24 12:52:26
8	2	panduan-aplikasi/IKZGFcpZpajfWuy1uBP0RU8r5Ih3AZPPCaFaw2cF.zip	update-7.zip	90070	application/octet-stream	2026-09-24 12:52:26	2026-09-24 12:52:26
9	3	panduan-aplikasi/3lp6VhwOlViVGT3UaWkciRBtB6dIe0atJQke97Ge.zip	update-8.0.zip	140804	application/octet-stream	2026-09-24 12:53:07	2026-09-24 12:53:07
10	3	panduan-aplikasi/Lo6dcfc51aXwRqWedojZeP1LT2SsZqUlsBYbZxbt.zip	update-8.1 perbaikan-cache-tampilan.zip	1994	application/octet-stream	2026-09-24 12:53:07	2026-09-24 12:53:07
11	3	panduan-aplikasi/0VMINMOI1cQf6SnCLXH6Tasy6vk1BiEHPU9Fy3p2.zip	update-8.2 perbaikan-lampiran2a-tampilan.zip	4088	application/octet-stream	2026-09-24 12:53:07	2026-09-24 12:53:07
12	3	panduan-aplikasi/NCj8p1qPdtSTPJs5wxS2BBMkon46SSl81HZQTz2R.zip	update-8.3 perbaikan-lampiran2a-export-tandatangan.zip	9033	application/octet-stream	2026-09-24 12:53:07	2026-09-24 12:53:07
13	3	panduan-aplikasi/yWZdI3RgVWAeqg6OJYWDAiUTJslQHYdW2wyIygh5.zip	update-8.4 perbaikan-lampiran2a-export-v2.zip	2185	application/octet-stream	2026-09-24 12:53:07	2026-09-24 12:53:07
14	3	panduan-aplikasi/SJaN9vQch3gLj2Uc3iKtu7v3Z1AmETdXSS25ywtC.zip	update-8.5 perbaikan-lampiran2a-export-v3.zip	2141	application/octet-stream	2026-09-24 12:53:07	2026-09-24 12:53:07
15	4	panduan-aplikasi/JOBETYgp60EC2DtupCw9lMI8dOJjxBxgMBQc7qe0.zip	update-9.0.zip	29260	application/octet-stream	2026-09-24 12:53:59	2026-09-24 12:53:59
16	4	panduan-aplikasi/rlh0umKgUgJvGCLqPh7gIVcoRJzMRJ9pcukSAOqs.zip	update-9.1 perbaikan-lampiran2b-export-tandatangan.zip	9625	application/octet-stream	2026-09-24 12:53:59	2026-09-24 12:53:59
17	5	panduan-aplikasi/IEwnMwdHsbLnJM4Y5DBpzF9Tb5ZnqJLbwu0PdyPh.zip	update-10.zip	24364	application/octet-stream	2026-09-24 12:54:42	2026-09-24 12:54:42
18	6	panduan-aplikasi/EVWn25ElkjHRuB8ssHK6J2BRRlvYnVGl2pooSGOn.zip	update-11.0.zip	18388	application/octet-stream	2026-09-24 12:55:29	2026-09-24 12:55:29
19	6	panduan-aplikasi/i7UQtL3wm1QuBOGtHjjFqLZxa8HVD9WU7JuXE3pM.zip	update-11.1 perbaikan-lampiran2c-judul-kolom-grup.zip	3513	application/octet-stream	2026-09-24 12:55:29	2026-09-24 12:55:29
20	6	panduan-aplikasi/LzTKtFNIR5ov7ortJ9hHJQn9FlwnBZ7VporzVJ8c.zip	update-11.2 perbaikan-lampiran2c-dropdown-export.zip	17402	application/octet-stream	2026-09-24 12:55:29	2026-09-24 12:55:29
21	6	panduan-aplikasi/bN3gTQwjPHIwisYpzg0Br2Dqw0Yh7vJZ0JhwZhuz.zip	update-11.3 perbaikan-ui-pendataan-ops.zip	15245	application/octet-stream	2026-09-24 12:55:29	2026-09-24 12:55:29
22	6	panduan-aplikasi/Heb8EONAuI8lHvlhpKhpWWOS5go7LS0q8iqfeCl3.zip	update-11.4 perbaikan-export-dropdown-2a-2b-2c.zip	10541	application/octet-stream	2026-09-24 12:55:29	2026-09-24 12:55:29
23	6	panduan-aplikasi/HwNcYnWT6KbbN5TuaR9QdfPaMcOyM2vG3eI2w5Ni.zip	update-11.5 perbaikan-ukuran-tombol-lampiran-2a-2b-2c.zip	25332	application/octet-stream	2026-09-24 12:55:29	2026-09-24 12:55:29
24	6	panduan-aplikasi/rKhcUxMo5R3GkGQjt5ByMLCs3v99df7qrexjiWRB.zip	update-11.6 pengawas-currency-kalender.zip	48027	application/octet-stream	2026-09-24 12:55:29	2026-09-24 12:55:29
25	6	panduan-aplikasi/SzfOzIzN7SSkAR9qVcEOptdr07aHoP4gTN5NAKCA.zip	update-11.7 onboarding-gate-profil-sekolah.zip	35270	application/octet-stream	2026-09-24 12:55:29	2026-09-24 12:55:29
26	6	panduan-aplikasi/jhqrD8wIWreQDI5X9Vckwk3l0qRApqBXlcCLImhb.zip	update-11.8 foto-sk-ops-dashboard.zip	165920	application/octet-stream	2026-09-24 12:55:29	2026-09-24 12:55:29
27	6	panduan-aplikasi/P8HJDpjSWIwezjWc46BiV3BjbF7hZKO2Cp7UYCnz.zip	update-11.9 autorefresh-zoom-filter.zip	112001	application/octet-stream	2026-09-24 12:55:29	2026-09-24 12:55:29
28	6	panduan-aplikasi/YpP7bOI3LuPMuYEVlImPMIAcoiXl2WBzwg158E7z.zip	update-11.10 bg-redirect-popup.zip	119081	application/octet-stream	2026-09-24 12:55:29	2026-09-24 12:55:29
29	7	panduan-aplikasi/JVPl9WzFKWcz6p9aZJAz9f4yxCOnm38h1qgkIGPg.zip	update-12.0 password-policy.zip	92558	application/octet-stream	2026-09-24 13:04:58	2026-09-24 13:04:58
30	7	panduan-aplikasi/S2smmAmBQ4HM5horiRAxuVsLiWNZOWrBJRz3WzyD.zip	update-12.1 warna huruf-zoom-tabel-reset-password-golongan.zip	131515	application/octet-stream	2026-09-24 13:04:58	2026-09-24 13:04:58
31	7	panduan-aplikasi/jPf25VGaQdRUd4TEM9Kzr8yHIHTQFsBCBXUlH9gI.zip	update-12.2 menu-unduhan-lampiran-huruf-registrasi.zip	189176	application/octet-stream	2026-09-24 13:04:58	2026-09-24 13:04:58
32	7	panduan-aplikasi/TfLSxyO03aFyOuOURhom1xXFk9CzvgxeXHAqk24D.zip	update-12.3 wa-nuptk-nip-foto-sk-ops-bold-registrasi-tandatangan-pdf.zip	123631	application/octet-stream	2026-09-24 13:04:58	2026-09-24 13:04:58
33	7	panduan-aplikasi/qf0tzGUws5wOjAzAPuxtoZPdo3dKN6iyB1b4MyBd.zip	update-12.4 ikon-landing-transparan-melayang-perbaikan-email-reset.zip	92515	application/octet-stream	2026-09-24 13:04:58	2026-09-24 13:04:58
34	7	panduan-aplikasi/yaOEiSxg2aAjQ1uk8hq9edCswIEsLKgrAJRBkj4S.zip	update-12.5 zoom-identitas-bosp-rekap-rkas.zip	107057	application/octet-stream	2026-09-24 13:04:58	2026-09-24 13:04:58
35	7	panduan-aplikasi/yR3qnN4vGJqZ2GPhCZ3DbLeGnXbTdzEp4FxiZlga.zip	update-12.6 zoom-input-langsung-garis-biru-rekap-rkas.zip	94134	application/octet-stream	2026-09-24 13:04:58	2026-09-24 13:04:58
36	7	panduan-aplikasi/kGCtpybe1wk1bupYSnWz5eiqWj2QTqPHe8QOKOd3.zip	update-12.7 baris-jumlah-tinggi-baris-rekap-rkas.zip	92405	application/octet-stream	2026-09-24 13:04:58	2026-09-24 13:04:58
37	7	panduan-aplikasi/taW5BUsxVR8ow6vKtLD1Rl7rl4hHt7cp5Q1d5NUJ.zip	update-12.7 rupiah-warna-kolom-rekap-rkas.zip	94660	application/octet-stream	2026-09-24 13:04:58	2026-09-24 13:04:58
38	7	panduan-aplikasi/N0ZK5IVdMp3JQzQ6sCBuve2xIUIjypRGg65MuPQv.zip	update-12.8 rumus-jml-sesudah-selisih-rekap-rkas.zip	104198	application/octet-stream	2026-09-24 13:04:58	2026-09-24 13:04:58
39	7	panduan-aplikasi/AxdyCyWbKeLdRG1byfwUJPWwyviSV247qWefYtRH.zip	update-12.9 penerimaan-honor-ptk.zip	115936	application/octet-stream	2026-09-24 13:04:58	2026-09-24 13:04:58
40	7	panduan-aplikasi/SxJ2xIW2LMXCQRdeZT7lU2leusZECfiU2BCDb11X.zip	update-12.10 input-langsung-honor-ptk.zip	99440	application/octet-stream	2026-09-24 13:04:58	2026-09-24 13:04:58
41	8	panduan-aplikasi/2MyyhyNVnqZFud5rLx7TbuY0NHUwpPl0EOHYtKhp.zip	update-12.11 honor-ptk-perbaikan-tambah-tab-urutan.zip	98225	application/octet-stream	2026-09-24 13:05:36	2026-09-24 13:05:36
42	8	panduan-aplikasi/FzI4JzWn7TU96eVrOkCCVBWKjRsRfwD6mBoRd9TU.zip	update-12.12 langganan-daya-jasa-dan-perbaikan-tombol.zip	129127	application/octet-stream	2026-09-24 13:05:36	2026-09-24 13:05:36
43	8	panduan-aplikasi/PDJ44ik7eCvUfEJvxLjg08jMUkCY55GIKQGDNzZu.zip	update-12.13 perbaikan-jumlah-otomatis-dan-tabel-diringkas.zip	133509	application/octet-stream	2026-09-24 13:05:36	2026-09-24 13:05:36
44	8	panduan-aplikasi/fsYZHaCKlgGPZ9hx8jNPNL8s8nwPqwScywvThd1U.zip	update-12.14 belanja-pemeliharaan-bangunan-dan-ringkas-daya-jasa.zip	118243	application/octet-stream	2026-09-24 13:05:36	2026-09-24 13:05:36
45	8	panduan-aplikasi/tmrfmbEZuCjFPBtQ8V1NdHMj8BxApBA9M99OtOc8.zip	update-12.15 belanja-pemeliharaan-pc.zip	112500	application/octet-stream	2026-09-24 13:05:36	2026-09-24 13:05:36
46	8	panduan-aplikasi/uE9mouLTnOov8wtKEc3T9jWY3kXtFahxM8DX4s3V.zip	update-12.16 biaya-lomba-dan-honor-kegiatan.zip	131222	application/octet-stream	2026-09-24 13:05:36	2026-09-24 13:05:36
47	8	panduan-aplikasi/B7lRMzIe22RpW77Dpzq252qDOcyvfeJZkHH4tkKf.zip	update-12.17 belanja-honor-kegiatan-makan-minum-perjalanan-dinas.zip	110132	application/octet-stream	2026-09-24 13:05:36	2026-09-24 13:05:36
48	8	panduan-aplikasi/t5RGaG2mD1A5a1OuIVrBLoSpiojogV0KGeIwWCdp.zip	update-12.18 rincian-belanja-modal-dan-barang-habis-pakai.zip	60589	application/octet-stream	2026-09-24 13:05:36	2026-09-24 13:05:36
49	8	panduan-aplikasi/v79t2TFOQi7MHdLqvUSFIPUmQFU43MKcMPYJlG8O.zip	update-12.19 total-honor-ptk-per-sekolah-dan-keseluruhan.zip	101131	application/octet-stream	2026-09-24 13:05:36	2026-09-24 13:05:36
50	8	panduan-aplikasi/NvoHIY7Cm4pDV2xTgS5csUqz4rhkbHXIyT5ftZJL.zip	update-12.20 pajak-bosp-reguler.zip	122974	application/octet-stream	2026-09-24 13:05:36	2026-09-24 13:05:36
51	9	panduan-aplikasi/PIASHq1V0vYNQ6BHDqcNKxjE5yV4GaFVCLLWQv6e.zip	update-12.21 perbaikan-tab-rekap-dan-rincian-pajak-per-jenis.zip	115920	application/octet-stream	2026-09-24 13:06:28	2026-09-24 13:06:28
52	9	panduan-aplikasi/9Q4jffXJrgAuW6qpzdb49um6234vuhMdU97b3Sim.zip	update-12.22 pajak-bosp-reguler-part24.zip	115920	application/octet-stream	2026-09-24 13:06:28	2026-09-24 13:06:28
53	9	panduan-aplikasi/jRaynLZ62bEuUGiR3t2q4KiTMUAoky1NmAGgyvXv.zip	update-12.23 excel-rekap-no-jumlah-dan-label-pph4.zip	12854	application/octet-stream	2026-09-24 13:06:28	2026-09-24 13:06:28
54	9	panduan-aplikasi/ucYM8amL1uDAgI9aMacOowrjgtoD8w8NuuIXfyXq.zip	update-12.24 pajak-bosp-reguler-part25.zip	12854	application/octet-stream	2026-09-24 13:06:28	2026-09-24 13:06:28
55	9	panduan-aplikasi/JeqaeDu95lhk45umYzZy8cVdilKqd4B4QiVptYa5.zip	update-12.25 perbaikan-baris-jumlah-tab-rekap.zip	18349	application/octet-stream	2026-09-24 13:06:28	2026-09-24 13:06:28
56	9	panduan-aplikasi/irGO3iNgDRMfgrjmcUGbeIYnAdMDKkaM1c8gkrMA.zip	update-12.26 pajak-bosp-reguler-part26.zip	18349	application/octet-stream	2026-09-24 13:06:28	2026-09-24 13:06:28
57	9	panduan-aplikasi/xS06oK80jYLhTI8ZjZ5A84a8Iy1c4aAw4DcgBnB6.zip	update-12.27 total-daya-jasa-per-sekolah-dan-keseluruhan.zip	19462	application/octet-stream	2026-09-24 13:06:28	2026-09-24 13:06:28
58	9	panduan-aplikasi/SILqmrGmhNZ28RewvqqqvpR6pSSmEY4PJ6Nxn81b.zip	update-12.28 baris-jumlah-pemeliharaan-dan-lomba.zip	58886	application/octet-stream	2026-09-24 13:06:28	2026-09-24 13:06:28
59	9	panduan-aplikasi/VBCwUAJWh6ENRMN1UO35IPiTh2rCwKfujEPEervT.zip	update-12.29 baris-jumlah-honor-modal-habis-pakai.zip	36696	application/octet-stream	2026-09-24 13:06:28	2026-09-24 13:06:28
60	9	panduan-aplikasi/FNL9v7aBJsSo18shev9udLIlRUFtrx31nSIvwDZR.zip	update-12.30 dana-bosp-tahap.zip	21096	application/octet-stream	2026-09-24 13:06:28	2026-09-24 13:06:28
61	10	panduan-aplikasi/H75QeIkLnJknEFiqyQS5QPMFMvlz5ZFrTTzYUsZC.zip	update-13.31 saldo-tw-tarik-tunai.zip	21498	application/octet-stream	2026-09-24 13:07:06	2026-09-24 13:07:06
62	11	panduan-aplikasi/ZdzF0lhRDcs9I0hd0LpgWMset5HjqZTQWdbZHqo6.zip	update-14.32 laporan-realisasi-bosp.zip	45833	application/octet-stream	2026-09-24 13:07:48	2026-09-24 13:07:48
63	11	panduan-aplikasi/VFNk6gIGdFWffMTzYtlJS5VZFcG0Bf5RoHfuiDtO.zip	update-14.45 formulir-bos-k7bc-20260923.zip	41360	application/octet-stream	2026-09-24 13:07:48	2026-09-24 13:07:48
64	11	panduan-aplikasi/8kpYp7C0bl59Ve20U7wbFwEFmx39ojo2CaJnohlo.zip	update-14.46 formulir-bos-k7b-perbaikan-20260923-2.zip	25028	application/octet-stream	2026-09-24 13:07:48	2026-09-24 13:07:48
65	12	panduan-aplikasi/AEJG7C82yG9jQgOhOpuzsWAuaHfQWOzI30mjijcG.zip	update-15.32 belanja-barang-habis-pakai-otomatis.zip	22036	application/octet-stream	2026-09-24 13:09:27	2026-09-24 13:09:27
66	12	panduan-aplikasi/p638MgoWslBPyMBzF7966ocOLCKsOdpueIkB4wIO.zip	update-15.33 jasa-tendik-daya-jasa-otomatis.zip	24635	application/octet-stream	2026-09-24 13:09:27	2026-09-24 13:09:27
67	12	panduan-aplikasi/qF8LejvqKuT5B0ImYrU2PNivLl5dKKJgtsWA8J0Y.zip	update-15.34 pemeliharaan-otomatis.zip	26541	application/octet-stream	2026-09-24 13:09:27	2026-09-24 13:09:27
68	12	panduan-aplikasi/Fdy5SEKGBNXHus9pUo0vrHcwuiNR3NZlQGNPmh2g.zip	update-15.35 belanja-barang-jasa-otomatis.zip	30317	application/octet-stream	2026-09-24 13:09:27	2026-09-24 13:09:27
69	12	panduan-aplikasi/kfjWNoz8ZR84tiImbuSKZlHVYv2M0VMe5xwOxcye.zip	update-15.36 realisasi-bosp-total-otomatis.zip	26623	application/octet-stream	2026-09-24 13:09:27	2026-09-24 13:09:27
70	12	panduan-aplikasi/Cted4kqA6lIr3HLio2PeiTt1N4tVDJ6tEhp1yQLz.zip	update-15.37 saldo-otomatis-alert-rekap.zip	26303	application/octet-stream	2026-09-24 13:09:27	2026-09-24 13:09:27
71	12	panduan-aplikasi/saxpWDxYqUlboYSrZDkjHhFNIMsXURVImx4AskQp.zip	update-15.38 saldo-awal-penerimaan-otomatis.zip	31164	application/octet-stream	2026-09-24 13:09:27	2026-09-24 13:09:27
72	12	panduan-aplikasi/j57fluouZIEByKeLupOgUy5AP4DuZHcvE8KVjaba.zip	update-15.39 stock-opname.zip	120899	application/octet-stream	2026-09-24 13:09:27	2026-09-24 13:09:27
73	12	panduan-aplikasi/Roqq4nRDW2FJY8KHt556a67MqRmJPFK9piF37efY.zip	update-15.40 stock-opname-rumus-otomatis.zip	37341	application/octet-stream	2026-09-24 13:09:27	2026-09-24 13:09:27
74	12	panduan-aplikasi/hBvaApPp8NtMOM6mSbfXU5T3nj4JAsS0s9TmXTbh.zip	update-15.41 tab-bmd.zip	128734	application/octet-stream	2026-09-24 13:09:27	2026-09-24 13:09:27
75	12	panduan-aplikasi/gkN55nOHgwMk37QdUWKF8gfPOHram0B64jHqUqrI.zip	update-15.42 export-bmd-otomatis.zip	8039	application/octet-stream	2026-09-24 13:09:27	2026-09-24 13:09:27
76	12	panduan-aplikasi/AeLjzkKUKuErahLIKXldcFwUuv6bURKGUSafNCGp.zip	update-15.43 ppk-otomatis-rekap-bmd.zip	135106	application/octet-stream	2026-09-24 13:09:27	2026-09-24 13:09:27
77	12	panduan-aplikasi/QWsVsfmb9C8HiF4ew6Q9b30qAq00wZyC0FPxOghj.zip	update-15.44 permintaan-2026-09-23.zip	204712	application/octet-stream	2026-09-24 13:09:27	2026-09-24 13:09:27
78	12	panduan-aplikasi/Q9x9oEzFjB7Ljk5CwXSCpGAlY0sRlcWcHPCpuQqo.zip	update-15.45 formulir-bos-k7bc-round3-20260923.zip	38336	application/octet-stream	2026-09-24 13:09:27	2026-09-24 13:09:27
79	13	panduan-aplikasi/4QBXyfXx1TchUOvCgd6vFEdEbIqusfxXyqXSXcyQ.zip	update-15.46 formulir-bos-k7bc-round4-20260923.zip	20714	application/octet-stream	2026-09-24 13:10:23	2026-09-24 13:10:23
80	13	panduan-aplikasi/EXf3q8NJ0lSnhvLrMiiiDtM7NR9QhTBpYcg8RCef.zip	update-15.47 formulir-bos-k7bc-round5-20260923.zip	28157	application/octet-stream	2026-09-24 13:10:23	2026-09-24 13:10:23
81	13	panduan-aplikasi/fMDN9obGnMyyraAczJX8cpMj9DoVku3h2FVkAvCP.zip	update-15.48 formulir-bos-k7bc-verval-realisasi-bosp-round6-20260923.zip	134377	application/octet-stream	2026-09-24 13:10:23	2026-09-24 13:10:23
82	13	panduan-aplikasi/5Pr2kqIJk2d0BxfVCof7QXX0r466xJK5L4YVUXc9.zip	update-15.49 round7-verval-refresh-reset.zip	43434	application/octet-stream	2026-09-24 13:10:23	2026-09-24 13:10:23
83	13	panduan-aplikasi/yQWa9lDsBPlicAanVvB3HkEue7Sk1z8OcGbQGlFd.zip	update-15.50 round8-gerbang-per-triwulan-dan-ttd-pdf.zip	53377	application/octet-stream	2026-09-24 13:10:23	2026-09-24 13:10:23
84	13	panduan-aplikasi/7rb4zLSkMxBITIclzfonhhg8mt1xxrfZCx8F74Fr.zip	update-15.51 round8b-timeline-pekerjaan-fondasi.zip	23540	application/octet-stream	2026-09-24 13:10:23	2026-09-24 13:10:23
85	13	panduan-aplikasi/GsruzCdyB1ahKn7XCu2zrmTsTH1QRhZF2xB5ADkK.zip	update-15.52 round9a-panel-validasi-dan-kuncian-verval.zip	54460	application/octet-stream	2026-09-24 13:10:23	2026-09-24 13:10:23
86	13	panduan-aplikasi/XZlbdhIyqjTfFENIDJRVbV0fOZ0nEXcX4QX6JHi9.zip	update-15.53 round9b-timeline-per-triwulan.zip	19831	application/octet-stream	2026-09-24 13:10:23	2026-09-24 13:10:23
87	13	panduan-aplikasi/BKLKugLcO62Vc47thyv73cw5gDmS2qqFwakNY4Ar.zip	update-15.54 round9c-popup-info-timeline-login.zip	103263	application/octet-stream	2026-09-24 13:10:23	2026-09-24 13:10:23
88	13	panduan-aplikasi/6tEmfExBJipNXWACxE9wbpAvRwHYeSgM4luwclEn.zip	update-15.55 round10-kuncian-ui-timeline-pekerjaan.zip	266028	application/octet-stream	2026-09-24 13:10:23	2026-09-24 13:10:23
89	13	panduan-aplikasi/j7JtaH7PYqaCkuLOle82m5ghx89AbruByAI9e7B4.zip	update-15.56 round11-fix-penerimaan-dana-bos-superadmin.zip	31036	application/octet-stream	2026-09-24 13:10:23	2026-09-24 13:10:23
90	13	panduan-aplikasi/uuCoZv43juFb52yBvRLseo6n21UJKyTyhYPvcfl5.zip	update-15.57 round12-timeline-terapkan-reset-per-kategori.zip	15693	application/octet-stream	2026-09-24 13:10:23	2026-09-24 13:10:23
91	13	panduan-aplikasi/lZ2RuInXq8hZmTE9mnn1ARgiggYTevRBf7HQokP8.zip	update-15.58 round13-dashboard-pendataan-ops-bosp.zip	124700	application/octet-stream	2026-09-24 13:10:23	2026-09-24 13:10:23
92	13	panduan-aplikasi/MndUo6tOZ0dVNyYGqGtJOKCksdjbSSwq0nkBjBO6.zip	update-15.59 round14-dashboard-superadmin-registrasi-validasi.zip	19360	application/octet-stream	2026-09-24 13:10:23	2026-09-24 13:10:23
93	13	panduan-aplikasi/rjnuXL0YhE6ibwGSAXmVBYjTeNw6KFQ0AN18qSFD.zip	update-15.60 round15-dashboard-superadmin-pagination.zip	20433	application/octet-stream	2026-09-24 13:10:23	2026-09-24 13:10:23
94	14	panduan-aplikasi/m0W41rfnDp5nsaTyIIx7s8XdHMV4Ufzuj5PXQsvu.zip	update-15.60 round15-dashboard-superadmin-pagination.zip	20433	application/octet-stream	2026-09-24 13:10:49	2026-09-24 13:10:49
95	14	panduan-aplikasi/l8mqqFPX21vTDSRGlKGlwpyohSgG36j75DOo5fHC.zip	update-15.61 round16-fix-pagination-scroll.zip	5578	application/octet-stream	2026-09-24 13:10:49	2026-09-24 13:10:49
96	14	panduan-aplikasi/ln5CP9f5SAUd1m1bKSLBFwnpu84MMIMOXS68kVrm.zip	update-15.62 round16-landing-page-publik.zip	31030	application/octet-stream	2026-09-24 13:10:49	2026-09-24 13:10:49
97	14	panduan-aplikasi/Hwu4lXTrLPQqjl2MzVLPuJdHkEOmlwpwLzPxM8F8.zip	update-15.63 round17-selector-triwulan-landing-dan-kembali-login.zip	16572	application/octet-stream	2026-09-24 13:10:49	2026-09-24 13:10:49
98	14	panduan-aplikasi/eXPVcUgcVhYiYwiafvXlFKjwYJrl7ChpsOd4xf0T.rar	update-15.64 round18-surat-tpg-20260924.rar	112848	application/octet-stream	2026-09-24 13:10:49	2026-09-24 13:10:49
99	14	panduan-aplikasi/tHgKxqZSdDnVOegD5cqkKnPOSyA7XHRuVJZLVwA0.zip	update-15.65 round19-kop-surat-format-formulir-20260924.zip	48238	application/octet-stream	2026-09-24 13:10:49	2026-09-24 13:10:49
100	14	panduan-aplikasi/3bW0CeqygFWtID1usSsHgHiVtF1uENvz6UWmhKoc.zip	update-15.66 round20-perbaikan-nomor-ttd-spasi-validasi-kop-20260924.zip	20140	application/octet-stream	2026-09-24 13:10:49	2026-09-24 13:10:49
101	14	panduan-aplikasi/W7DJLWs2YJg52wXwrjRqHiygkeIC02gbmGNx4lai.zip	update-15.67 round21-surat-pernyataan-cetak-semua-20260924.zip	36929	application/octet-stream	2026-09-24 13:10:49	2026-09-24 13:10:49
102	14	panduan-aplikasi/kju4e3rh4ByBRFZkBAcAIQW9H2F6YqtPu2yEQa49.zip	update-15.68 round22-perbaikan-pernyataan-dan-pindah-cetak-gabungan-20260924.zip	125439	application/octet-stream	2026-09-24 13:10:49	2026-09-24 13:10:49
103	14	panduan-aplikasi/A9hH9UVaW5gCU8apdVRsMgv8fVmXDBuohkUUJCt4.zip	update-15.69 round23-materai-dan-setting-kertas-unduhan-20260924.zip	25323	application/octet-stream	2026-09-24 13:10:49	2026-09-24 13:10:49
104	14	panduan-aplikasi/PA6RO9Gb9F4i1ifJzljQxQwbhrmxwpnBLCzK3LBi.zip	update-15.70 round24-warna-tab-materai-panduan-aplikasi-20260924.zip	150674	application/octet-stream	2026-09-24 13:10:49	2026-09-24 13:10:49
105	14	panduan-aplikasi/DosY1ykUJYX8YSq5a9G3d31YyPLvznyfzyaUiwzv.zip	update-15.71 round25-panduan-multi-file-link-dan-menu-backup-20260924.zip	59700	application/octet-stream	2026-09-24 13:10:49	2026-09-24 13:10:49
106	14	panduan-aplikasi/B0Os2ypn2kIiLNoft9bOY8MQAHkhxqaN0wokW8MG.zip	update-15.72 round26-baca-selengkapnya-scrollbar-dan-unduh-semua-20260924.zip	32329	application/octet-stream	2026-09-24 13:10:49	2026-09-24 13:10:49
\.


--
-- Data for Name: panduan_aplikasi_link; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.panduan_aplikasi_link (id, panduan_aplikasi_id, link_drive, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: pendataan_bosp; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.pendataan_bosp (id, created_by, created_at, updated_at, profil_sekolah_id, nuptk, nama, nip, jk, tempat_lahir, tanggal_lahir, status_kepegawaian, pendidikan_terakhir, jurusan, nama_perguruan_tinggi, no_whatsapp) FROM stdin;
1	9	2026-09-09 13:50:53	2026-09-09 13:50:53	11	770709909090	IIN PARLINA	198001202009022009	P	Sukabumi	1980-01-02	PNS	S1	Akuntansi	STIE Sukabumi	087979799789
2	10	2026-09-17 12:13:37	2026-09-23 12:50:50	61	6666699795875	AM-FZHM	197020012000011007	L	Sukabumi	2026-09-17	PNS	SMA	\N	\N	082111317836
\.


--
-- Data for Name: pendataan_ops; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.pendataan_ops (id, created_by, created_at, updated_at, profil_sekolah_id, nuptk, nama, nip, jk, tempat_lahir, tanggal_lahir, status_kepegawaian, pendidikan_terakhir, jurusan, nama_perguruan_tinggi, no_whatsapp, foto_ops, sk_ops) FROM stdin;
1	\N	2026-09-05 02:24:11	2026-09-05 02:24:11	11	58887777757	Budi	1585878758687	L	Sukabumi	2026-09-05	ASN PPPK	SMA	\N	\N	087779798798	foto-ops/klWm3jte4qRI6F3JbqIEIj7vyMw7Ky4AflKLD9Iz.jpg	sk-ops/xSfqpRZIzRIBWbrzKIb6yUcBQ4ZKwLGTDLbVBQFv.pdf
\.


--
-- Data for Name: penerimaan_honor_ptk; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.penerimaan_honor_ptk (id, profil_sekolah_id, tahun, triwulan, nuptk, nama_penerima, volume, satuan, tarif_harga, jumlah_honor, tanggal_bayar, created_by, created_at, updated_at) FROM stdin;
5	61	2026	1	\N	Abdul	1	bulan	10000	10000	2026-09-23	10	2026-09-23 11:16:23	2026-09-23 11:16:41
8	2	2026	1	\N	AM	1	bulan	100000	100000	2026-09-24	1	2026-09-24 02:07:25	2026-09-24 02:07:42
\.


--
-- Data for Name: pengaturan_tampilan; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.pengaturan_tampilan (id, background_landing, background_login, warna_halaman, warna_menu, ukuran_huruf_halaman, ukuran_huruf_menu, jenis_huruf_halaman, jenis_huruf_menu, created_at, updated_at, warna_huruf_menu, warna_huruf_landing, warna_huruf_login, warna_huruf_registrasi, ukuran_huruf_registrasi, jenis_huruf_registrasi) FROM stdin;
1	tampilan/0Ik4oNWwSfoPArMMDlEz9CARXdrZZ2KfoKoN0GBB.png	tampilan/bg_6a9b8961a91862.95219547.jpg	#000000	#121212	sedang	kecil	Poppins	Nunito	2026-09-03 21:35:38	2026-09-05 06:26:00	#ffffff	#ffffff	#ffffff	#f7f7f8	sedang	Figtree
\.


--
-- Data for Name: profil_sekolah; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.profil_sekolah (id, npsn, nama_sekolah, nama_kepala_sekolah, nip_kepala_sekolah, alamat_sekolah, created_at, updated_at, status, kecamatan, status_kepegawaian_kepsek, nama_bendahara, nip_bendahara, status_kepegawaian_bendahara, nama_pengawas, nip_pengawas, no_whatsapp_kepala_sekolah, kode_upb, subrayon, kop_surat) FROM stdin;
3	20202387	SMP N 1 CICANTAYAN	\N	\N	\N	2026-09-04 11:55:52	2026-09-17 11:59:04	negeri	Cicantayan	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
2	20202384	SMP NEGERI 1 CARINGIN	\N	\N	\N	2026-09-04 11:55:52	2026-09-17 11:58:23	negeri	Caringin	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
4	20202385	SMP NEGERI 1 CIBADAK	\N	\N	\N	2026-09-04 11:55:52	2026-09-17 11:58:32	negeri	Cibadak	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
5	20202332	SMP NEGERI 2 CIBADAK	\N	\N	\N	2026-09-04 11:55:52	2026-09-17 11:58:39	negeri	Cibadak	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
1	20202363	SMP NEGERI 3 CIBADAK	\N	\N	\N	2026-09-03 13:22:57	2026-09-17 11:58:46	negeri	Cibadak	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
6	20258417	SMP NEGERI 4 CIBADAK	\N	\N	\N	2026-09-04 11:55:52	2026-09-17 11:58:57	negeri	Cibadak	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
7	20202415	SMP NEGERI 1 CIKIDANG	\N	\N	\N	2026-09-04 11:55:52	2026-09-17 11:59:10	negeri	Cikidang	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
8	20202337	SMP NEGERI 2 CIKIDANG	\N	\N	\N	2026-09-04 11:55:52	2026-09-17 11:59:16	negeri	Cikidang	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
9	20271227	SMP NEGERI 3 CIKIDANG	\N	\N	\N	2026-09-04 11:55:52	2026-09-17 11:59:23	negeri	Cikidang	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
10	20202380	SMP NEGERI 1 NAGRAK	\N	\N	\N	2026-09-04 11:55:52	2026-09-17 11:59:37	negeri	Nagrak	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
12	20246694	SMP AL FALAAH CARINGIN	\N	\N	\N	2026-09-04 11:55:52	2026-09-17 11:59:49	swasta	Caringin	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
14	20252035	SMP ISLAM AL MAFTUH CARINGIN	\N	\N	\N	2026-09-04 11:55:52	2026-09-17 12:01:38	swasta	Caringin	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
17	69758323	SMP ISLAM TERPADU ALGHAZALI	\N	\N	\N	2026-09-04 11:55:52	2026-09-17 12:01:44	swasta	Caringin	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
15	20258063	SMP IT ATTAISIRIYAH	\N	\N	\N	2026-09-04 11:55:52	2026-09-17 12:01:53	swasta	Caringin	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
16	69726933	SMP KHALIFAH BOARDING SCHOOL	\N	\N	\N	2026-09-04 11:55:52	2026-09-17 12:02:00	swasta	Caringin	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
18	70045754	SMP MA`ARIF DARUSYAKIRIN	\N	\N	\N	2026-09-04 11:55:52	2026-09-17 12:02:05	swasta	Caringin	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
13	20247059	SMP PGRI CARINGIN	\N	\N	\N	2026-09-04 11:55:52	2026-09-17 12:02:12	swasta	Caringin	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
27	20247024	SMP AL MANSHURIYAH	\N	\N	\N	2026-09-04 11:55:52	2026-09-17 12:02:19	swasta	Cibadak	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
36	20268143	SMP ISLAM NURUL FIKRI	\N	\N	\N	2026-09-04 11:55:53	2026-09-17 12:02:24	swasta	Cibadak	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
38	69888447	SMP ISLAM TERPADU SUNAN GUNUNG JATI	\N	\N	\N	2026-09-04 11:55:53	2026-09-17 12:02:29	swasta	Cibadak	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
35	20257488	SMP IT KH AHMAD SANUSI	\N	\N	\N	2026-09-04 11:55:53	2026-09-17 12:02:34	swasta	Cibadak	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
29	20247043	SMP MARDI YUANA CIBADAK	\N	\N	\N	2026-09-04 11:55:52	2026-09-17 12:02:39	swasta	Cibadak	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
40	69995708	SMP MUHAMMADIYAH BOARDING SCHOOL AL KARIMAH	\N	\N	\N	2026-09-04 11:55:53	2026-09-17 12:02:46	swasta	Cibadak	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
30	20247053	SMP PEMBANGUNAN	\N	\N	\N	2026-09-04 11:55:52	2026-09-17 12:02:51	swasta	Cibadak	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
37	60728951	SMP PGRI BATU ASIH	\N	\N	\N	2026-09-04 11:55:53	2026-09-17 12:02:57	swasta	Cibadak	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
31	20247061	SMP PGRI CIBADAK	\N	\N	\N	2026-09-04 11:55:52	2026-09-17 12:03:02	swasta	Cibadak	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
39	69991673	SMP PLUS AL-BASYARI	\N	\N	\N	2026-09-04 11:55:53	2026-09-17 12:03:08	swasta	Cibadak	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
34	20255892	SMP PLUS LUQMANULHAKIM	\N	\N	\N	2026-09-04 11:55:52	2026-09-17 12:03:14	swasta	Cibadak	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
32	20247073	SMP TAMANSISWA CIBADAK	\N	\N	\N	2026-09-04 11:55:52	2026-09-17 12:03:19	swasta	Cibadak	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
33	20247076	SMP UNWANUL FALAH	\N	\N	\N	2026-09-04 11:55:52	2026-09-17 12:03:26	swasta	Cibadak	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
28	20247040	SMPIT AL UMMAH	\N	\N	\N	2026-09-04 11:55:52	2026-09-17 12:03:32	swasta	Cibadak	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
41	69996101	SMPIT INSAN CENDEKIA AD-DA`WAH	\N	\N	\N	2026-09-04 11:55:53	2026-09-17 12:03:37	swasta	Cibadak	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
25	70053817	SMP DARUL MUTAFAQQIH	\N	\N	\N	2026-09-04 11:55:52	2026-09-17 12:03:43	swasta	Cicantayan	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
20	20252036	SMP ISLAM AL IRSYAD	\N	\N	\N	2026-09-04 11:55:52	2026-09-17 12:03:50	swasta	Cicantayan	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
24	69889086	SMP ISLAM CIJATI	\N	\N	\N	2026-09-04 11:55:52	2026-09-17 12:03:55	swasta	Cicantayan	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
21	20253235	SMP ISLAM HEGARMANAH	\N	\N	\N	2026-09-04 11:55:52	2026-09-17 12:04:01	swasta	Cicantayan	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
19	20247039	SMP ISLAM NURUL HUDA	\N	\N	\N	2026-09-04 11:55:52	2026-09-17 12:04:08	swasta	Cicantayan	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
22	20254145	SMP PLUS NURUL HUDA	\N	\N	\N	2026-09-04 11:55:52	2026-09-17 12:04:14	swasta	Cicantayan	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
23	20258447	SMP WIDYA PRAJA CIJALINGAN	\N	\N	\N	2026-09-04 11:55:52	2026-09-17 12:04:19	swasta	Cicantayan	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
26	70055742	SMPIT BANI SYAIBAH	\N	\N	\N	2026-09-04 11:55:52	2026-09-17 12:04:24	swasta	Cicantayan	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
49	70040118	SMP BINA BANGSA	\N	\N	\N	2026-09-04 11:55:53	2026-09-17 12:04:29	swasta	Cikidang	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
48	70029234	SMP BINA INSANI CIKIDANG	\N	\N	\N	2026-09-04 11:55:53	2026-09-17 12:04:38	swasta	Cikidang	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
44	20268172	SMP ISLAM CIBUNGUR	\N	\N	\N	2026-09-04 11:55:53	2026-09-17 12:04:44	swasta	Cikidang	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
43	20258087	SMP ISLAM CIHERANG	\N	\N	\N	2026-09-04 11:55:53	2026-09-17 12:04:50	swasta	Cikidang	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
50	70055709	SMP PESANTREN UNGGUL AL BAYAN CIKIDANG	\N	\N	\N	2026-09-04 11:55:53	2026-09-17 12:04:57	swasta	Cikidang	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
46	69758322	SMP PGRI 2 CIKIDANG	\N	\N	\N	2026-09-04 11:55:53	2026-09-17 12:05:02	swasta	Cikidang	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
45	69727425	SMP PGRI 3 CIKIDANG	\N	\N	\N	2026-09-04 11:55:53	2026-09-17 12:05:07	swasta	Cikidang	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
42	20247064	SMP PGRI CIKIDANG	\N	\N	\N	2026-09-04 11:55:53	2026-09-17 12:05:13	swasta	Cikidang	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
47	70007364	SMP PUTRA PERINTIS	\N	\N	\N	2026-09-04 11:55:53	2026-09-17 12:05:19	swasta	Cikidang	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
52	20247022	SMP AL-ISMA ILIYAH	\N	\N	\N	2026-09-04 11:55:53	2026-09-17 12:05:24	swasta	Nagrak	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
53	20247025	SMP IKO ANATA PUTRA	\N	\N	\N	2026-09-04 11:55:53	2026-09-17 12:05:29	swasta	Nagrak	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
55	20252039	SMP ISLAM CENDIKIA	\N	\N	\N	2026-09-04 11:55:53	2026-09-17 12:05:34	swasta	Nagrak	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
59	69959929	SMP IT AT-TAKWIN	\N	\N	\N	2026-09-04 11:55:53	2026-09-17 12:05:47	swasta	Nagrak	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
60	69965448	SMP IT DARUL IBTIDA	\N	\N	\N	2026-09-04 11:55:53	2026-09-17 12:05:52	swasta	Nagrak	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
54	20247051	SMP MUHAMMADIYAH 8 NAGRAK	\N	\N	\N	2026-09-04 11:55:53	2026-09-17 12:05:56	swasta	Nagrak	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
51	20202394	SMP PGRI 1 NAGRAK	\N	\N	\N	2026-09-04 11:55:53	2026-09-17 12:06:01	swasta	Nagrak	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
56	20269268	SMP PGRI 2 NAGRAK	\N	\N	\N	2026-09-04 11:55:53	2026-09-17 12:06:07	swasta	Nagrak	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
57	60729089	SMP TERPADU BUDHI MULIA	\N	\N	\N	2026-09-04 11:55:53	2026-09-17 12:06:13	swasta	Nagrak	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
58	69756866	SMPS PGRI 3 NAGRAK	\N	\N	\N	2026-09-04 11:55:53	2026-09-17 12:06:18	swasta	Nagrak	\N	\N	\N	\N	\N	\N	\N	\N	Cibadak	\N
61	12345678	SMPS UJI COBA	Abdul Muiz, S. Kom	198201202022211006	Jl. Raya Karangtengah	2026-09-17 12:08:26	2026-09-23 12:51:12	swasta	Nagrak	ASN PPPK	Sinta Sriwijayanti, S.IP	198501252024212009	ASN PPPK	Faiza Zahira HM	19801022026012001	082111317836	12345678	Cibadak	\N
11	20202376	SMP NEGERI 2 NAGRAK	Yana Rudiana, S. Pd	196801202007121009	Jl. Nagrak	2026-09-04 11:55:52	2026-09-24 09:35:42	negeri	Nagrak	PNS	Iin Parlina	198001202011012003	PNS	DR. H. Ujang Syarif, M. Pd	196701201990011007	082111317836	\N	Cibadak	kop-surat/kL673QOLDfwPkvoKpofx6r0mKrfGYky6jM9jxTBX.png
\.


--
-- Data for Name: rekap_rkas; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.rekap_rkas (id, profil_sekolah_id, tahun, anggaran_bosp, pegawai_sebelum, pegawai_realisasi_tahap1, pegawai_perubahan_tahap2, pegawai_jml_sesudah, pegawai_selisih, pemeliharaan_sebelum, pemeliharaan_realisasi_tahap1, pemeliharaan_perubahan_tahap2, pemeliharaan_jml_sesudah, pemeliharaan_selisih, barjas_sebelum, barjas_realisasi_tahap1, barjas_perubahan_tahap2, barjas_jml_sesudah, barjas_selisih, peralatan_mesin_sebelum, peralatan_mesin_realisasi_tahap1, peralatan_mesin_perubahan_tahap2, peralatan_mesin_jml_sesudah, peralatan_mesin_selisih, aset_lainnya_sebelum, aset_lainnya_realisasi_tahap1, aset_lainnya_perubahan_tahap2, aset_lainnya_jml_sesudah, aset_lainnya_selisih, jumlah_sebelum, jumlah_sesudah, jumlah_selisih, created_by, created_at, updated_at) FROM stdin;
6	6	2026	\N	\N	\N	\N	0	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	0	0	0	1	2026-09-22 23:47:20	2026-09-22 23:47:20
7	4	2026	\N	\N	\N	\N	0	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	0	0	0	1	2026-09-22 23:47:21	2026-09-22 23:47:21
5	5	2026	\N	\N	\N	\N	0	0	\N	\N	\N	0	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	0	0	0	1	2026-09-22 23:47:19	2026-09-22 23:47:24
1	45	2024	\N	\N	\N	\N	0	0	\N	\N	\N	0	0	\N	\N	\N	0	0	\N	\N	\N	0	0	\N	\N	\N	0	0	0	0	0	1	2026-09-09 13:43:42	2026-09-09 22:02:20
3	12	2026	\N	\N	\N	\N	0	0	\N	\N	\N	0	0	\N	\N	\N	0	0	\N	\N	\N	0	0	\N	\N	\N	0	0	0	0	0	1	2026-09-09 21:03:34	2026-09-09 22:02:21
8	3	2026	\N	\N	\N	\N	\N	\N	\N	\N	\N	0	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	0	0	0	1	2026-09-22 23:47:24	2026-09-22 23:47:24
9	61	2026	\N	\N	\N	\N	0	0	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	\N	0	0	0	1	2026-09-23 13:12:33	2026-09-23 13:12:33
2	11	2026	0	\N	\N	\N	0	0	\N	\N	\N	0	0	\N	\N	\N	0	0	\N	\N	\N	0	0	\N	\N	\N	0	0	0	0	0	9	2026-09-09 14:03:58	2026-09-23 13:54:32
4	2	2026	5500000	\N	\N	\N	0	0	\N	\N	\N	0	0	\N	\N	\N	0	0	\N	\N	\N	0	0	\N	\N	\N	0	0	0	0	0	1	2026-09-09 21:25:30	2026-09-24 01:59:50
\.


--
-- Data for Name: rincian_belanja_barang_habis_pakai; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.rincian_belanja_barang_habis_pakai (id, profil_sekolah_id, tahun, triwulan, kode_upb, nama_barang, nama_merk_barang, volume, satuan, harga_satuan, total_harga, asal_usul, tanggal, keterangan, created_by, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: rincian_belanja_modal; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.rincian_belanja_modal (id, profil_sekolah_id, jenis, tahun, triwulan, kode_upb, nama_barang, nama_merk_barang, volume, satuan, harga_satuan, total_harga, asal_usul, tanggal, keterangan, created_by, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: rincian_belanja_modal_bmd; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.rincian_belanja_modal_bmd (id, profil_sekolah_id, tahun, triwulan, bentuk_kontrak, atribusi, jumlah_termin, ppk, nomor_dokumen, tanggal_perolehan, penyedia, kode_belanja, rekening_belanja, jenis_aset, sub_sub_rincian_objek, jumlah, satuan, harga_satuan, total, no_bast, tanggal_bast, keterangan_bos, nomor_surat_pernyataan, tanggal_surat_pernyataan, nama_pengurus_barang, jabatan, pejabat_penata_usaha, nama_barang, spesifikasi_nama_barang, spesifikasi_lain, merk_pengarang, keterangan_bosp, created_by, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: rincian_pemeliharaan; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.rincian_pemeliharaan (id, profil_sekolah_id, jenis, tahun, triwulan, kode_upb, nama_barang, nama_merk_barang, volume, satuan, harga_satuan, total_harga, asal_usul, tanggal, keterangan, created_by, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: rincian_pemeliharaan_pc; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.rincian_pemeliharaan_pc (id, profil_sekolah_id, jenis, tahun, triwulan, kode_upb, nama_barang, nama_merk_barang, volume, satuan, harga_satuan, total_harga, asal_usul, tanggal, keterangan, created_by, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) FROM stdin;
xqgOr8LThYb4EUlqItk3WgCU1zvrSTJtPjJygAvx	\N	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:156.0) Gecko/20100101 Firefox/156.0	eyJfdG9rZW4iOiJFQ2ZDWVRBWFVIYmZCZFZWWUFoTGh3RWt1Zk9MR0V1ZTJIbk9YTFlVIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJ1cmwiOnsiaW50ZW5kZWQiOiJodHRwOlwvXC8xMjcuMC4wLjE6ODAwMFwvcHJvZmlsLXNla29sYWgifSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC8/aGFsYW1hbk9wcz0zIiwicm91dGUiOiJiZXJhbmRhIn19	1790254197
47v4cQ5DvGhhEjvoWPOsE7ECLWmmeyyRy2ojhQNQ	\N	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36	eyJfdG9rZW4iOiJxZ244a0l4SGtiMWJONVZtc2dUM1ZJWWRCMHdPOGlPZXJ1OXZIbkpxIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvMTI3LjAuMC4xOjgwMDAiLCJyb3V0ZSI6ImJlcmFuZGEifX0=	1790289029
2BAoCAl5kVYYnneN9yzE1c17m7ZgBhZ6tFZzBj7n	\N	127.0.0.1	Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36	eyJfdG9rZW4iOiJaNjFqc0xib2NmMWsxSHd0eG9IVnowY3dYSWd3VzM4bk5OVU1Nak85IiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvMTI3LjAuMC4xOjgwMDAiLCJyb3V0ZSI6ImJlcmFuZGEifX0=	1790260168
\.


--
-- Data for Name: stock_opname_barang_persediaan; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.stock_opname_barang_persediaan (id, profil_sekolah_id, tahun, triwulan, nama_barang, satuan, harga, saldo_awal_kuantitas, saldo_awal_jumlah, penerimaan_kuantitas, penerimaan_jumlah, pengeluaran_kuantitas, pengeluaran_jumlah, saldo_akhir_kuantitas, saldo_akhir_jumlah, keterangan, created_by, created_at, updated_at, rincian_belanja_barang_habis_pakai_id) FROM stdin;
\.


--
-- Data for Name: surat_tpg; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.surat_tpg (id, profil_sekolah_id, tahun, triwulan, jenis, nomor_surat, tanggal_surat, created_at, updated_at, tahun_pelajaran) FROM stdin;
4	11	2026	1	pernyataan	\N	2026-09-24	2026-09-24 11:09:28	2026-09-24 11:37:47	2026/2027
2	11	2026	1	rekomendasi	higjkkjgkjgkjggjgjgjh	2026-09-24	2026-09-24 08:42:06	2026-09-24 08:42:42	\N
1	11	2026	1	penghentian	23.4.2.22.222/Sekret-BKPSDM/2026	\N	2026-09-24 08:41:23	2026-09-24 08:50:25	\N
3	11	2026	2	penghentian	bkjbkkjbkjbkjbvkjbkjbkjbkjbkjkk	2026-09-24	2026-09-24 09:38:29	2026-09-24 09:39:20	\N
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.users (id, username, password, level_akses, nama_sekolah, jabatan, remember_token, created_at, updated_at, profil_sekolah_id, must_change_password, is_approved) FROM stdin;
10	abdulmuizfaiza2@gmail.com	$2y$12$fMkbftgvDiAao9ECc/jjLO0b8mjLWzswSRJIOY7.hAInEc5GxnRMa	admin_bosp	SMPS UJI COBA	Admin BOSP	\N	2026-09-17 12:11:39	2026-09-17 12:12:39	61	f	t
1	superadmin	$2y$12$DWty.aI.Yal/bzeOznezP.Pl0KUGBXTOEMd2QfiQX9Wl2z7PopvEy	superadmin	\N	\N	tMnwo9kRQosBwFQ1aTf8HX6FdEC5NiYJM2ddzX69PVHKuFANRFhKPCOVJ1iu	2026-09-03 13:19:43	2026-09-05 00:43:14	\N	f	t
9	komisariat.cibadak@gmail.com	$2y$12$q9GfNQSwGGwtzCKogKJptetOZGAlb83ZMKs2kFfdNWCQSeks05Wye	admin_bosp	SMP NEGERI 2 NAGRAK	Admin BOSP	\N	2026-09-09 13:47:57	2026-09-09 13:48:49	11	f	t
7	superadmin1	$2y$12$.WgIz9oDElqm1G4yuIhiROkpkEzLE/n9JmzjHPGGfcW9owMrzKa/y	superadmin	\N	\N	\N	2026-09-05 00:41:33	2026-09-05 00:41:33	\N	f	t
\.


--
-- Data for Name: verval_realisasi_bosp; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.verval_realisasi_bosp (id, profil_sekolah_id, tahun, triwulan, status, diverval_oleh, diverval_pada, created_at, updated_at) FROM stdin;
9	61	2026	1	sesuai	10	2026-09-24 02:13:02	2026-09-24 02:13:02	2026-09-24 02:13:02
\.


--
-- Name: backups_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.backups_id_seq', 1, false);


--
-- Name: belanja_honor_kegiatan_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.belanja_honor_kegiatan_id_seq', 1, false);


--
-- Name: biaya_pendaftaran_lomba_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.biaya_pendaftaran_lomba_id_seq', 1, false);


--
-- Name: dana_bosp_tahap_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.dana_bosp_tahap_id_seq', 6, true);


--
-- Name: deadline_pekerjaan_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.deadline_pekerjaan_id_seq', 35, true);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- Name: formulir_bos_k7_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.formulir_bos_k7_id_seq', 5, true);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: lampiran_2a_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.lampiran_2a_id_seq', 1, false);


--
-- Name: lampiran_2b_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.lampiran_2b_id_seq', 1, false);


--
-- Name: lampiran_2c_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.lampiran_2c_id_seq', 1, false);


--
-- Name: langganan_daya_jasa_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.langganan_daya_jasa_id_seq', 1, true);


--
-- Name: laporan_realisasi_bosp_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.laporan_realisasi_bosp_id_seq', 1, true);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.migrations_id_seq', 57, true);


--
-- Name: pajak_bosp_reguler_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.pajak_bosp_reguler_id_seq', 2, true);


--
-- Name: panduan_aplikasi_file_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.panduan_aplikasi_file_id_seq', 106, true);


--
-- Name: panduan_aplikasi_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.panduan_aplikasi_id_seq', 14, true);


--
-- Name: panduan_aplikasi_link_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.panduan_aplikasi_link_id_seq', 1, false);


--
-- Name: pendataan_bosp_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.pendataan_bosp_id_seq', 2, true);


--
-- Name: pendataan_ops_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.pendataan_ops_id_seq', 1, true);


--
-- Name: penerimaan_honor_ptk_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.penerimaan_honor_ptk_id_seq', 8, true);


--
-- Name: pengaturan_tampilan_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.pengaturan_tampilan_id_seq', 1, true);


--
-- Name: profil_sekolah_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.profil_sekolah_id_seq', 61, true);


--
-- Name: rekap_rkas_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.rekap_rkas_id_seq', 9, true);


--
-- Name: rincian_belanja_barang_habis_pakai_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.rincian_belanja_barang_habis_pakai_id_seq', 2, true);


--
-- Name: rincian_belanja_modal_bmd_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.rincian_belanja_modal_bmd_id_seq', 2, true);


--
-- Name: rincian_belanja_modal_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.rincian_belanja_modal_id_seq', 1, false);


--
-- Name: rincian_pemeliharaan_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.rincian_pemeliharaan_id_seq', 1, false);


--
-- Name: rincian_pemeliharaan_pc_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.rincian_pemeliharaan_pc_id_seq', 1, false);


--
-- Name: stock_opname_barang_persediaan_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.stock_opname_barang_persediaan_id_seq', 1, true);


--
-- Name: surat_tpg_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.surat_tpg_id_seq', 4, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.users_id_seq', 10, true);


--
-- Name: verval_realisasi_bosp_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.verval_realisasi_bosp_id_seq', 9, true);


--
-- Name: backups backups_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.backups
    ADD CONSTRAINT backups_pkey PRIMARY KEY (id);


--
-- Name: belanja_honor_kegiatan belanja_honor_kegiatan_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.belanja_honor_kegiatan
    ADD CONSTRAINT belanja_honor_kegiatan_pkey PRIMARY KEY (id);


--
-- Name: biaya_pendaftaran_lomba biaya_pendaftaran_lomba_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.biaya_pendaftaran_lomba
    ADD CONSTRAINT biaya_pendaftaran_lomba_pkey PRIMARY KEY (id);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: dana_bosp_tahap dana_bosp_tahap_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.dana_bosp_tahap
    ADD CONSTRAINT dana_bosp_tahap_pkey PRIMARY KEY (id);


--
-- Name: dana_bosp_tahap dana_bosp_tahap_profil_sekolah_id_tahun_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.dana_bosp_tahap
    ADD CONSTRAINT dana_bosp_tahap_profil_sekolah_id_tahun_unique UNIQUE (profil_sekolah_id, tahun);


--
-- Name: deadline_pekerjaan deadline_pekerjaan_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.deadline_pekerjaan
    ADD CONSTRAINT deadline_pekerjaan_pkey PRIMARY KEY (id);


--
-- Name: deadline_pekerjaan deadline_pekerjaan_tahun_kunci_menu_triwulan_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.deadline_pekerjaan
    ADD CONSTRAINT deadline_pekerjaan_tahun_kunci_menu_triwulan_unique UNIQUE (tahun, kunci_menu, triwulan);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: formulir_bos_k7 formulir_bos_k7_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.formulir_bos_k7
    ADD CONSTRAINT formulir_bos_k7_pkey PRIMARY KEY (id);


--
-- Name: formulir_bos_k7 formulir_bos_k7_profil_sekolah_id_tahun_bulan_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.formulir_bos_k7
    ADD CONSTRAINT formulir_bos_k7_profil_sekolah_id_tahun_bulan_unique UNIQUE (profil_sekolah_id, tahun, bulan);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: lampiran_2a lampiran_2a_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.lampiran_2a
    ADD CONSTRAINT lampiran_2a_pkey PRIMARY KEY (id);


--
-- Name: lampiran_2b lampiran_2b_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.lampiran_2b
    ADD CONSTRAINT lampiran_2b_pkey PRIMARY KEY (id);


--
-- Name: lampiran_2c lampiran_2c_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.lampiran_2c
    ADD CONSTRAINT lampiran_2c_pkey PRIMARY KEY (id);


--
-- Name: langganan_daya_jasa langganan_daya_jasa_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.langganan_daya_jasa
    ADD CONSTRAINT langganan_daya_jasa_pkey PRIMARY KEY (id);


--
-- Name: laporan_realisasi_bosp laporan_realisasi_bosp_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.laporan_realisasi_bosp
    ADD CONSTRAINT laporan_realisasi_bosp_pkey PRIMARY KEY (id);


--
-- Name: laporan_realisasi_bosp laporan_realisasi_bosp_profil_sekolah_id_tahun_triwulan_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.laporan_realisasi_bosp
    ADD CONSTRAINT laporan_realisasi_bosp_profil_sekolah_id_tahun_triwulan_unique UNIQUE (profil_sekolah_id, tahun, triwulan);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: pajak_bosp_reguler pajak_bosp_reguler_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pajak_bosp_reguler
    ADD CONSTRAINT pajak_bosp_reguler_pkey PRIMARY KEY (id);


--
-- Name: pajak_bosp_reguler pajak_bosp_reguler_profil_sekolah_id_tahun_bulan_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pajak_bosp_reguler
    ADD CONSTRAINT pajak_bosp_reguler_profil_sekolah_id_tahun_bulan_unique UNIQUE (profil_sekolah_id, tahun, bulan);


--
-- Name: panduan_aplikasi_file panduan_aplikasi_file_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.panduan_aplikasi_file
    ADD CONSTRAINT panduan_aplikasi_file_pkey PRIMARY KEY (id);


--
-- Name: panduan_aplikasi_link panduan_aplikasi_link_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.panduan_aplikasi_link
    ADD CONSTRAINT panduan_aplikasi_link_pkey PRIMARY KEY (id);


--
-- Name: panduan_aplikasi panduan_aplikasi_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.panduan_aplikasi
    ADD CONSTRAINT panduan_aplikasi_pkey PRIMARY KEY (id);


--
-- Name: pendataan_bosp pendataan_bosp_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pendataan_bosp
    ADD CONSTRAINT pendataan_bosp_pkey PRIMARY KEY (id);


--
-- Name: pendataan_bosp pendataan_bosp_profil_sekolah_id_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pendataan_bosp
    ADD CONSTRAINT pendataan_bosp_profil_sekolah_id_unique UNIQUE (profil_sekolah_id);


--
-- Name: pendataan_ops pendataan_ops_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pendataan_ops
    ADD CONSTRAINT pendataan_ops_pkey PRIMARY KEY (id);


--
-- Name: pendataan_ops pendataan_ops_profil_sekolah_id_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pendataan_ops
    ADD CONSTRAINT pendataan_ops_profil_sekolah_id_unique UNIQUE (profil_sekolah_id);


--
-- Name: penerimaan_honor_ptk penerimaan_honor_ptk_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.penerimaan_honor_ptk
    ADD CONSTRAINT penerimaan_honor_ptk_pkey PRIMARY KEY (id);


--
-- Name: penerimaan_honor_ptk penerimaan_honor_ptk_profil_sekolah_id_tahun_triwulan_nuptk_uni; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.penerimaan_honor_ptk
    ADD CONSTRAINT penerimaan_honor_ptk_profil_sekolah_id_tahun_triwulan_nuptk_uni UNIQUE (profil_sekolah_id, tahun, triwulan, nuptk);


--
-- Name: pengaturan_tampilan pengaturan_tampilan_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pengaturan_tampilan
    ADD CONSTRAINT pengaturan_tampilan_pkey PRIMARY KEY (id);


--
-- Name: profil_sekolah profil_sekolah_npsn_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.profil_sekolah
    ADD CONSTRAINT profil_sekolah_npsn_unique UNIQUE (npsn);


--
-- Name: profil_sekolah profil_sekolah_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.profil_sekolah
    ADD CONSTRAINT profil_sekolah_pkey PRIMARY KEY (id);


--
-- Name: rekap_rkas rekap_rkas_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rekap_rkas
    ADD CONSTRAINT rekap_rkas_pkey PRIMARY KEY (id);


--
-- Name: rekap_rkas rekap_rkas_profil_sekolah_id_tahun_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rekap_rkas
    ADD CONSTRAINT rekap_rkas_profil_sekolah_id_tahun_unique UNIQUE (profil_sekolah_id, tahun);


--
-- Name: rincian_belanja_barang_habis_pakai rincian_belanja_barang_habis_pakai_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rincian_belanja_barang_habis_pakai
    ADD CONSTRAINT rincian_belanja_barang_habis_pakai_pkey PRIMARY KEY (id);


--
-- Name: rincian_belanja_modal_bmd rincian_belanja_modal_bmd_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rincian_belanja_modal_bmd
    ADD CONSTRAINT rincian_belanja_modal_bmd_pkey PRIMARY KEY (id);


--
-- Name: rincian_belanja_modal rincian_belanja_modal_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rincian_belanja_modal
    ADD CONSTRAINT rincian_belanja_modal_pkey PRIMARY KEY (id);


--
-- Name: rincian_pemeliharaan_pc rincian_pemeliharaan_pc_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rincian_pemeliharaan_pc
    ADD CONSTRAINT rincian_pemeliharaan_pc_pkey PRIMARY KEY (id);


--
-- Name: rincian_pemeliharaan rincian_pemeliharaan_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rincian_pemeliharaan
    ADD CONSTRAINT rincian_pemeliharaan_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: stock_opname_barang_persediaan stock_opname_barang_persediaan_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.stock_opname_barang_persediaan
    ADD CONSTRAINT stock_opname_barang_persediaan_pkey PRIMARY KEY (id);


--
-- Name: stock_opname_barang_persediaan stock_opname_rincian_id_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.stock_opname_barang_persediaan
    ADD CONSTRAINT stock_opname_rincian_id_unique UNIQUE (rincian_belanja_barang_habis_pakai_id);


--
-- Name: surat_tpg surat_tpg_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.surat_tpg
    ADD CONSTRAINT surat_tpg_pkey PRIMARY KEY (id);


--
-- Name: surat_tpg surat_tpg_unik; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.surat_tpg
    ADD CONSTRAINT surat_tpg_unik UNIQUE (profil_sekolah_id, tahun, triwulan, jenis);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: users users_username_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_username_unique UNIQUE (username);


--
-- Name: verval_realisasi_bosp verval_realisasi_bosp_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.verval_realisasi_bosp
    ADD CONSTRAINT verval_realisasi_bosp_pkey PRIMARY KEY (id);


--
-- Name: verval_realisasi_bosp verval_realisasi_bosp_profil_sekolah_id_tahun_triwulan_unique; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.verval_realisasi_bosp
    ADD CONSTRAINT verval_realisasi_bosp_profil_sekolah_id_tahun_triwulan_unique UNIQUE (profil_sekolah_id, tahun, triwulan);


--
-- Name: belanja_honor_kegiatan_jenis_tahun_triwulan_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX belanja_honor_kegiatan_jenis_tahun_triwulan_index ON public.belanja_honor_kegiatan USING btree (jenis, tahun, triwulan);


--
-- Name: biaya_pendaftaran_lomba_tahun_triwulan_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX biaya_pendaftaran_lomba_tahun_triwulan_index ON public.biaya_pendaftaran_lomba USING btree (tahun, triwulan);


--
-- Name: cache_expiration_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration);


--
-- Name: cache_locks_expiration_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration);


--
-- Name: failed_jobs_connection_queue_failed_at_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX failed_jobs_connection_queue_failed_at_index ON public.failed_jobs USING btree (connection, queue, failed_at);


--
-- Name: formulir_bos_k7_tahun_bulan_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX formulir_bos_k7_tahun_bulan_index ON public.formulir_bos_k7 USING btree (tahun, bulan);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: lampiran_2a_profil_sekolah_id_triwulan_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX lampiran_2a_profil_sekolah_id_triwulan_index ON public.lampiran_2a USING btree (profil_sekolah_id, triwulan);


--
-- Name: lampiran_2b_profil_sekolah_id_triwulan_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX lampiran_2b_profil_sekolah_id_triwulan_index ON public.lampiran_2b USING btree (profil_sekolah_id, triwulan);


--
-- Name: lampiran_2c_profil_sekolah_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX lampiran_2c_profil_sekolah_id_index ON public.lampiran_2c USING btree (profil_sekolah_id);


--
-- Name: lampiran_2c_profil_sekolah_id_triwulan_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX lampiran_2c_profil_sekolah_id_triwulan_index ON public.lampiran_2c USING btree (profil_sekolah_id, triwulan);


--
-- Name: langganan_daya_jasa_tahun_triwulan_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX langganan_daya_jasa_tahun_triwulan_index ON public.langganan_daya_jasa USING btree (tahun, triwulan);


--
-- Name: laporan_realisasi_bosp_tahun_triwulan_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX laporan_realisasi_bosp_tahun_triwulan_index ON public.laporan_realisasi_bosp USING btree (tahun, triwulan);


--
-- Name: pajak_bosp_reguler_tahun_bulan_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX pajak_bosp_reguler_tahun_bulan_index ON public.pajak_bosp_reguler USING btree (tahun, bulan);


--
-- Name: penerimaan_honor_ptk_tahun_triwulan_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX penerimaan_honor_ptk_tahun_triwulan_index ON public.penerimaan_honor_ptk USING btree (tahun, triwulan);


--
-- Name: rincian_belanja_barang_habis_pakai_tahun_triwulan_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX rincian_belanja_barang_habis_pakai_tahun_triwulan_index ON public.rincian_belanja_barang_habis_pakai USING btree (tahun, triwulan);


--
-- Name: rincian_belanja_modal_bmd_tahun_triwulan_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX rincian_belanja_modal_bmd_tahun_triwulan_index ON public.rincian_belanja_modal_bmd USING btree (tahun, triwulan);


--
-- Name: rincian_belanja_modal_jenis_tahun_triwulan_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX rincian_belanja_modal_jenis_tahun_triwulan_index ON public.rincian_belanja_modal USING btree (jenis, tahun, triwulan);


--
-- Name: rincian_pemeliharaan_jenis_tahun_triwulan_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX rincian_pemeliharaan_jenis_tahun_triwulan_index ON public.rincian_pemeliharaan USING btree (jenis, tahun, triwulan);


--
-- Name: rincian_pemeliharaan_pc_jenis_tahun_triwulan_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX rincian_pemeliharaan_pc_jenis_tahun_triwulan_index ON public.rincian_pemeliharaan_pc USING btree (jenis, tahun, triwulan);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: stock_opname_barang_persediaan_tahun_triwulan_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX stock_opname_barang_persediaan_tahun_triwulan_index ON public.stock_opname_barang_persediaan USING btree (tahun, triwulan);


--
-- Name: surat_tpg_tahun_triwulan_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX surat_tpg_tahun_triwulan_index ON public.surat_tpg USING btree (tahun, triwulan);


--
-- Name: verval_realisasi_bosp_tahun_triwulan_index; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX verval_realisasi_bosp_tahun_triwulan_index ON public.verval_realisasi_bosp USING btree (tahun, triwulan);


--
-- Name: backups backups_dibuat_oleh_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.backups
    ADD CONSTRAINT backups_dibuat_oleh_id_foreign FOREIGN KEY (dibuat_oleh_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: belanja_honor_kegiatan belanja_honor_kegiatan_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.belanja_honor_kegiatan
    ADD CONSTRAINT belanja_honor_kegiatan_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: belanja_honor_kegiatan belanja_honor_kegiatan_profil_sekolah_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.belanja_honor_kegiatan
    ADD CONSTRAINT belanja_honor_kegiatan_profil_sekolah_id_foreign FOREIGN KEY (profil_sekolah_id) REFERENCES public.profil_sekolah(id) ON DELETE CASCADE;


--
-- Name: biaya_pendaftaran_lomba biaya_pendaftaran_lomba_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.biaya_pendaftaran_lomba
    ADD CONSTRAINT biaya_pendaftaran_lomba_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: biaya_pendaftaran_lomba biaya_pendaftaran_lomba_profil_sekolah_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.biaya_pendaftaran_lomba
    ADD CONSTRAINT biaya_pendaftaran_lomba_profil_sekolah_id_foreign FOREIGN KEY (profil_sekolah_id) REFERENCES public.profil_sekolah(id) ON DELETE CASCADE;


--
-- Name: dana_bosp_tahap dana_bosp_tahap_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.dana_bosp_tahap
    ADD CONSTRAINT dana_bosp_tahap_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: dana_bosp_tahap dana_bosp_tahap_profil_sekolah_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.dana_bosp_tahap
    ADD CONSTRAINT dana_bosp_tahap_profil_sekolah_id_foreign FOREIGN KEY (profil_sekolah_id) REFERENCES public.profil_sekolah(id) ON DELETE CASCADE;


--
-- Name: deadline_pekerjaan deadline_pekerjaan_diatur_oleh_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.deadline_pekerjaan
    ADD CONSTRAINT deadline_pekerjaan_diatur_oleh_foreign FOREIGN KEY (diatur_oleh) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: formulir_bos_k7 formulir_bos_k7_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.formulir_bos_k7
    ADD CONSTRAINT formulir_bos_k7_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: formulir_bos_k7 formulir_bos_k7_profil_sekolah_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.formulir_bos_k7
    ADD CONSTRAINT formulir_bos_k7_profil_sekolah_id_foreign FOREIGN KEY (profil_sekolah_id) REFERENCES public.profil_sekolah(id) ON DELETE CASCADE;


--
-- Name: lampiran_2a lampiran_2a_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.lampiran_2a
    ADD CONSTRAINT lampiran_2a_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: lampiran_2a lampiran_2a_profil_sekolah_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.lampiran_2a
    ADD CONSTRAINT lampiran_2a_profil_sekolah_id_foreign FOREIGN KEY (profil_sekolah_id) REFERENCES public.profil_sekolah(id) ON DELETE CASCADE;


--
-- Name: lampiran_2b lampiran_2b_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.lampiran_2b
    ADD CONSTRAINT lampiran_2b_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: lampiran_2b lampiran_2b_profil_sekolah_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.lampiran_2b
    ADD CONSTRAINT lampiran_2b_profil_sekolah_id_foreign FOREIGN KEY (profil_sekolah_id) REFERENCES public.profil_sekolah(id) ON DELETE CASCADE;


--
-- Name: lampiran_2c lampiran_2c_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.lampiran_2c
    ADD CONSTRAINT lampiran_2c_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: lampiran_2c lampiran_2c_profil_sekolah_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.lampiran_2c
    ADD CONSTRAINT lampiran_2c_profil_sekolah_id_foreign FOREIGN KEY (profil_sekolah_id) REFERENCES public.profil_sekolah(id) ON DELETE CASCADE;


--
-- Name: langganan_daya_jasa langganan_daya_jasa_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.langganan_daya_jasa
    ADD CONSTRAINT langganan_daya_jasa_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: langganan_daya_jasa langganan_daya_jasa_profil_sekolah_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.langganan_daya_jasa
    ADD CONSTRAINT langganan_daya_jasa_profil_sekolah_id_foreign FOREIGN KEY (profil_sekolah_id) REFERENCES public.profil_sekolah(id) ON DELETE CASCADE;


--
-- Name: laporan_realisasi_bosp laporan_realisasi_bosp_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.laporan_realisasi_bosp
    ADD CONSTRAINT laporan_realisasi_bosp_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: laporan_realisasi_bosp laporan_realisasi_bosp_profil_sekolah_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.laporan_realisasi_bosp
    ADD CONSTRAINT laporan_realisasi_bosp_profil_sekolah_id_foreign FOREIGN KEY (profil_sekolah_id) REFERENCES public.profil_sekolah(id) ON DELETE CASCADE;


--
-- Name: pajak_bosp_reguler pajak_bosp_reguler_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pajak_bosp_reguler
    ADD CONSTRAINT pajak_bosp_reguler_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: pajak_bosp_reguler pajak_bosp_reguler_profil_sekolah_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pajak_bosp_reguler
    ADD CONSTRAINT pajak_bosp_reguler_profil_sekolah_id_foreign FOREIGN KEY (profil_sekolah_id) REFERENCES public.profil_sekolah(id) ON DELETE CASCADE;


--
-- Name: panduan_aplikasi_file panduan_aplikasi_file_panduan_aplikasi_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.panduan_aplikasi_file
    ADD CONSTRAINT panduan_aplikasi_file_panduan_aplikasi_id_foreign FOREIGN KEY (panduan_aplikasi_id) REFERENCES public.panduan_aplikasi(id) ON DELETE CASCADE;


--
-- Name: panduan_aplikasi_link panduan_aplikasi_link_panduan_aplikasi_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.panduan_aplikasi_link
    ADD CONSTRAINT panduan_aplikasi_link_panduan_aplikasi_id_foreign FOREIGN KEY (panduan_aplikasi_id) REFERENCES public.panduan_aplikasi(id) ON DELETE CASCADE;


--
-- Name: pendataan_bosp pendataan_bosp_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pendataan_bosp
    ADD CONSTRAINT pendataan_bosp_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: pendataan_bosp pendataan_bosp_profil_sekolah_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pendataan_bosp
    ADD CONSTRAINT pendataan_bosp_profil_sekolah_id_foreign FOREIGN KEY (profil_sekolah_id) REFERENCES public.profil_sekolah(id) ON DELETE CASCADE;


--
-- Name: pendataan_ops pendataan_ops_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pendataan_ops
    ADD CONSTRAINT pendataan_ops_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: pendataan_ops pendataan_ops_profil_sekolah_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.pendataan_ops
    ADD CONSTRAINT pendataan_ops_profil_sekolah_id_foreign FOREIGN KEY (profil_sekolah_id) REFERENCES public.profil_sekolah(id) ON DELETE CASCADE;


--
-- Name: penerimaan_honor_ptk penerimaan_honor_ptk_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.penerimaan_honor_ptk
    ADD CONSTRAINT penerimaan_honor_ptk_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: penerimaan_honor_ptk penerimaan_honor_ptk_profil_sekolah_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.penerimaan_honor_ptk
    ADD CONSTRAINT penerimaan_honor_ptk_profil_sekolah_id_foreign FOREIGN KEY (profil_sekolah_id) REFERENCES public.profil_sekolah(id) ON DELETE CASCADE;


--
-- Name: rekap_rkas rekap_rkas_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rekap_rkas
    ADD CONSTRAINT rekap_rkas_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: rekap_rkas rekap_rkas_profil_sekolah_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rekap_rkas
    ADD CONSTRAINT rekap_rkas_profil_sekolah_id_foreign FOREIGN KEY (profil_sekolah_id) REFERENCES public.profil_sekolah(id) ON DELETE CASCADE;


--
-- Name: rincian_belanja_barang_habis_pakai rincian_belanja_barang_habis_pakai_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rincian_belanja_barang_habis_pakai
    ADD CONSTRAINT rincian_belanja_barang_habis_pakai_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: rincian_belanja_barang_habis_pakai rincian_belanja_barang_habis_pakai_profil_sekolah_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rincian_belanja_barang_habis_pakai
    ADD CONSTRAINT rincian_belanja_barang_habis_pakai_profil_sekolah_id_foreign FOREIGN KEY (profil_sekolah_id) REFERENCES public.profil_sekolah(id) ON DELETE CASCADE;


--
-- Name: rincian_belanja_modal_bmd rincian_belanja_modal_bmd_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rincian_belanja_modal_bmd
    ADD CONSTRAINT rincian_belanja_modal_bmd_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: rincian_belanja_modal_bmd rincian_belanja_modal_bmd_profil_sekolah_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rincian_belanja_modal_bmd
    ADD CONSTRAINT rincian_belanja_modal_bmd_profil_sekolah_id_foreign FOREIGN KEY (profil_sekolah_id) REFERENCES public.profil_sekolah(id) ON DELETE CASCADE;


--
-- Name: rincian_belanja_modal rincian_belanja_modal_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rincian_belanja_modal
    ADD CONSTRAINT rincian_belanja_modal_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: rincian_belanja_modal rincian_belanja_modal_profil_sekolah_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rincian_belanja_modal
    ADD CONSTRAINT rincian_belanja_modal_profil_sekolah_id_foreign FOREIGN KEY (profil_sekolah_id) REFERENCES public.profil_sekolah(id) ON DELETE CASCADE;


--
-- Name: rincian_pemeliharaan rincian_pemeliharaan_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rincian_pemeliharaan
    ADD CONSTRAINT rincian_pemeliharaan_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: rincian_pemeliharaan_pc rincian_pemeliharaan_pc_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rincian_pemeliharaan_pc
    ADD CONSTRAINT rincian_pemeliharaan_pc_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: rincian_pemeliharaan_pc rincian_pemeliharaan_pc_profil_sekolah_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rincian_pemeliharaan_pc
    ADD CONSTRAINT rincian_pemeliharaan_pc_profil_sekolah_id_foreign FOREIGN KEY (profil_sekolah_id) REFERENCES public.profil_sekolah(id) ON DELETE CASCADE;


--
-- Name: rincian_pemeliharaan rincian_pemeliharaan_profil_sekolah_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.rincian_pemeliharaan
    ADD CONSTRAINT rincian_pemeliharaan_profil_sekolah_id_foreign FOREIGN KEY (profil_sekolah_id) REFERENCES public.profil_sekolah(id) ON DELETE CASCADE;


--
-- Name: stock_opname_barang_persediaan stock_opname_barang_persediaan_created_by_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.stock_opname_barang_persediaan
    ADD CONSTRAINT stock_opname_barang_persediaan_created_by_foreign FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: stock_opname_barang_persediaan stock_opname_barang_persediaan_profil_sekolah_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.stock_opname_barang_persediaan
    ADD CONSTRAINT stock_opname_barang_persediaan_profil_sekolah_id_foreign FOREIGN KEY (profil_sekolah_id) REFERENCES public.profil_sekolah(id) ON DELETE CASCADE;


--
-- Name: stock_opname_barang_persediaan stock_opname_barang_persediaan_rincian_belanja_barang_habis_pak; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.stock_opname_barang_persediaan
    ADD CONSTRAINT stock_opname_barang_persediaan_rincian_belanja_barang_habis_pak FOREIGN KEY (rincian_belanja_barang_habis_pakai_id) REFERENCES public.rincian_belanja_barang_habis_pakai(id) ON DELETE CASCADE;


--
-- Name: surat_tpg surat_tpg_profil_sekolah_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.surat_tpg
    ADD CONSTRAINT surat_tpg_profil_sekolah_id_foreign FOREIGN KEY (profil_sekolah_id) REFERENCES public.profil_sekolah(id) ON DELETE CASCADE;


--
-- Name: users users_profil_sekolah_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_profil_sekolah_id_foreign FOREIGN KEY (profil_sekolah_id) REFERENCES public.profil_sekolah(id) ON DELETE RESTRICT;


--
-- Name: verval_realisasi_bosp verval_realisasi_bosp_diverval_oleh_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.verval_realisasi_bosp
    ADD CONSTRAINT verval_realisasi_bosp_diverval_oleh_foreign FOREIGN KEY (diverval_oleh) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: verval_realisasi_bosp verval_realisasi_bosp_profil_sekolah_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.verval_realisasi_bosp
    ADD CONSTRAINT verval_realisasi_bosp_profil_sekolah_id_foreign FOREIGN KEY (profil_sekolah_id) REFERENCES public.profil_sekolah(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict dummy123

