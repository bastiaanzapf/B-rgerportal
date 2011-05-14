--
-- PostgreSQL database dump
--

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = off;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET escape_string_warning = off;

SET search_path = public, pg_catalog;

--
-- Name: referenz_typ; Type: TYPE; Schema: public; Owner: -
--

CREATE TYPE referenz_typ AS ENUM (
    'tagesordnung',
    'tagesordnungspunkt',
    'vorlage',
    'sitzungskalender',
    'anwesenheitsliste'
);


SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: benutzer; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE benutzer (
    mandant_id integer,
    benutzer_id integer NOT NULL,
    bezeichnung text
);


--
-- Name: benutzer_benutzer_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE benutzer_benutzer_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: benutzer_benutzer_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE benutzer_benutzer_id_seq OWNED BY benutzer.benutzer_id;


--
-- Name: instanz; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE instanz (
    instanz_id integer NOT NULL,
    referenz_id integer,
    retrieved timestamp without time zone,
    content bytea,
    hash text,
    parsed timestamp without time zone,
    content_type_reported text,
    mandant_id integer
);


--
-- Name: instanz_instanz_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE instanz_instanz_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: instanz_instanz_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE instanz_instanz_id_seq OWNED BY instanz.instanz_id;


--
-- Name: mandant; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE mandant (
    mandant_id integer NOT NULL,
    bezeichnung text
);


--
-- Name: referenz; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE referenz (
    referenz_id integer NOT NULL,
    original_key text,
    url text,
    post text,
    parent integer,
    "position" integer,
    instanz_entnommen integer,
    typ referenz_typ,
    original_description text,
    do_not_download boolean,
    mandant_id integer NOT NULL
);


--
-- Name: referenz_referenz_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE referenz_referenz_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: referenz_referenz_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE referenz_referenz_id_seq OWNED BY referenz.referenz_id;


--
-- Name: benutzer_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE benutzer ALTER COLUMN benutzer_id SET DEFAULT nextval('benutzer_benutzer_id_seq'::regclass);


--
-- Name: instanz_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE instanz ALTER COLUMN instanz_id SET DEFAULT nextval('instanz_instanz_id_seq'::regclass);


--
-- Name: referenz_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE referenz ALTER COLUMN referenz_id SET DEFAULT nextval('referenz_referenz_id_seq'::regclass);


--
-- Name: benutzer_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY benutzer
    ADD CONSTRAINT benutzer_pkey PRIMARY KEY (benutzer_id);


--
-- Name: instanz_hash_key; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY instanz
    ADD CONSTRAINT instanz_hash_key UNIQUE (hash);


--
-- Name: instanz_mandant_id_key; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY instanz
    ADD CONSTRAINT instanz_mandant_id_key UNIQUE (mandant_id, instanz_id);


--
-- Name: mandant_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY mandant
    ADD CONSTRAINT mandant_pkey PRIMARY KEY (mandant_id);


--
-- Name: referenz_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY referenz
    ADD CONSTRAINT referenz_pkey PRIMARY KEY (mandant_id, referenz_id);


--
-- Name: referenz_url_key; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY referenz
    ADD CONSTRAINT referenz_url_key UNIQUE (url, post);


--
-- Name: fki_; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX fki_ ON referenz USING btree (mandant_id, instanz_entnommen);


--
-- Name: fki_referenz; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX fki_referenz ON instanz USING btree (mandant_id, referenz_id);


--
-- Name: benutzer_mandant_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY benutzer
    ADD CONSTRAINT benutzer_mandant_id_fkey FOREIGN KEY (mandant_id) REFERENCES mandant(mandant_id);


--
-- Name: referenz; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY instanz
    ADD CONSTRAINT referenz FOREIGN KEY (mandant_id, referenz_id) REFERENCES referenz(mandant_id, referenz_id);


--
-- Name: referenz_mandant_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY referenz
    ADD CONSTRAINT referenz_mandant_id_fkey FOREIGN KEY (mandant_id) REFERENCES mandant(mandant_id);


--
-- Name: referenz_mandant_id_fkey1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY referenz
    ADD CONSTRAINT referenz_mandant_id_fkey1 FOREIGN KEY (mandant_id, instanz_entnommen) REFERENCES instanz(mandant_id, instanz_id);


--
-- PostgreSQL database dump complete
--

