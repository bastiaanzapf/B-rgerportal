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
-- Name: objekt_typ; Type: TYPE; Schema: public; Owner: basti
--

CREATE TYPE objekt_typ AS ENUM (
    'sitzung',
    'tagesordnung',
    'tagesordnungspunkt',
    'vorlage',
    'niederschrift',
    'unspezifiziert'
);


ALTER TYPE public.objekt_typ OWNER TO basti;

--
-- Name: referenz_typ; Type: TYPE; Schema: public; Owner: basti
--

CREATE TYPE referenz_typ AS ENUM (
    'tagesordnung',
    'tagesordnungspunkt',
    'vorlage'
);


ALTER TYPE public.referenz_typ OWNER TO basti;

SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: instanz; Type: TABLE; Schema: public; Owner: basti; Tablespace: 
--

CREATE TABLE instanz (
    instanz_id integer NOT NULL,
    referenz_id integer,
    retrieved timestamp without time zone,
    content oid
);


ALTER TABLE public.instanz OWNER TO basti;

--
-- Name: instanz_instanz_id_seq; Type: SEQUENCE; Schema: public; Owner: basti
--

CREATE SEQUENCE instanz_instanz_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.instanz_instanz_id_seq OWNER TO basti;

--
-- Name: instanz_instanz_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: basti
--

ALTER SEQUENCE instanz_instanz_id_seq OWNED BY instanz.instanz_id;


--
-- Name: referenz; Type: TABLE; Schema: public; Owner: basti; Tablespace: 
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
    original_description text
);


ALTER TABLE public.referenz OWNER TO basti;

--
-- Name: referenz_referenz_id_seq; Type: SEQUENCE; Schema: public; Owner: basti
--

CREATE SEQUENCE referenz_referenz_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


ALTER TABLE public.referenz_referenz_id_seq OWNER TO basti;

--
-- Name: referenz_referenz_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: basti
--

ALTER SEQUENCE referenz_referenz_id_seq OWNED BY referenz.referenz_id;


--
-- Name: instanz_id; Type: DEFAULT; Schema: public; Owner: basti
--

ALTER TABLE instanz ALTER COLUMN instanz_id SET DEFAULT nextval('instanz_instanz_id_seq'::regclass);


--
-- Name: referenz_id; Type: DEFAULT; Schema: public; Owner: basti
--

ALTER TABLE referenz ALTER COLUMN referenz_id SET DEFAULT nextval('referenz_referenz_id_seq'::regclass);


--
-- Name: instanz_instanz_id_key; Type: CONSTRAINT; Schema: public; Owner: basti; Tablespace: 
--

ALTER TABLE ONLY instanz
    ADD CONSTRAINT instanz_instanz_id_key UNIQUE (instanz_id);


--
-- Name: referenz_referenz_id_key; Type: CONSTRAINT; Schema: public; Owner: basti; Tablespace: 
--

ALTER TABLE ONLY referenz
    ADD CONSTRAINT referenz_referenz_id_key UNIQUE (referenz_id);


--
-- Name: instanz_referenz_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: basti
--

ALTER TABLE ONLY instanz
    ADD CONSTRAINT instanz_referenz_id_fkey FOREIGN KEY (referenz_id) REFERENCES referenz(referenz_id);


--
-- Name: referenz_instanz_entnommen_fkey; Type: FK CONSTRAINT; Schema: public; Owner: basti
--

ALTER TABLE ONLY referenz
    ADD CONSTRAINT referenz_instanz_entnommen_fkey FOREIGN KEY (instanz_entnommen) REFERENCES instanz(instanz_id);


--
-- Name: referenz_parent_fkey; Type: FK CONSTRAINT; Schema: public; Owner: basti
--

ALTER TABLE ONLY referenz
    ADD CONSTRAINT referenz_parent_fkey FOREIGN KEY (parent) REFERENCES referenz(referenz_id);


--
-- Name: public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM postgres;
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON SCHEMA public TO PUBLIC;


--
-- PostgreSQL database dump complete
--

