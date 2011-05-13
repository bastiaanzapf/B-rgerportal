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
-- Name: instanz; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE instanz (
    instanz_id integer NOT NULL,
    referenz_id integer,
    retrieved timestamp without time zone,
    content bytea,
    hash text,
    parsed timestamp without time zone,
    content_type_reported text
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
    do_not_download boolean
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
-- Name: instanz_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE instanz ALTER COLUMN instanz_id SET DEFAULT nextval('instanz_instanz_id_seq'::regclass);


--
-- Name: referenz_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE referenz ALTER COLUMN referenz_id SET DEFAULT nextval('referenz_referenz_id_seq'::regclass);


--
-- Name: instanz_hash_key; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY instanz
    ADD CONSTRAINT instanz_hash_key UNIQUE (hash);


--
-- Name: instanz_instanz_id_key; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY instanz
    ADD CONSTRAINT instanz_instanz_id_key UNIQUE (instanz_id);


--
-- Name: referenz_original_key_key; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY referenz
    ADD CONSTRAINT referenz_original_key_key UNIQUE (original_key);


--
-- Name: referenz_referenz_id_key; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY referenz
    ADD CONSTRAINT referenz_referenz_id_key UNIQUE (referenz_id);


--
-- Name: referenz_url_key; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY referenz
    ADD CONSTRAINT referenz_url_key UNIQUE (url, post);


--
-- Name: instanz_referenz_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY instanz
    ADD CONSTRAINT instanz_referenz_id_fkey FOREIGN KEY (referenz_id) REFERENCES referenz(referenz_id);


--
-- Name: referenz_instanz_entnommen_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY referenz
    ADD CONSTRAINT referenz_instanz_entnommen_fkey FOREIGN KEY (instanz_entnommen) REFERENCES instanz(instanz_id);


--
-- Name: referenz_parent_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY referenz
    ADD CONSTRAINT referenz_parent_fkey FOREIGN KEY (parent) REFERENCES referenz(referenz_id);


--
-- PostgreSQL database dump complete
--

