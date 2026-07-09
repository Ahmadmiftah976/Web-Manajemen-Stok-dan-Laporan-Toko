--
-- PostgreSQL database dump
--

\restrict czMmMDOJ8ObIltwrDa8UZuwycaqDUt8ehlMaMexiK8d7t0MjYWt0xdaIE9DGRIH

-- Dumped from database version 18.0
-- Dumped by pg_dump version 18.0

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

--
-- Name: fn_set_updated_at(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.fn_set_updated_at() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.fn_set_updated_at() OWNER TO postgres;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: products; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.products (
    id integer NOT NULL,
    name character varying(200) NOT NULL,
    sku character varying(50) NOT NULL,
    category character varying(100),
    harga_beli numeric(12,2) DEFAULT 0 NOT NULL,
    harga_jual numeric(12,2) DEFAULT 0 NOT NULL,
    stok_minimum integer DEFAULT 5 NOT NULL,
    description text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL,
    CONSTRAINT products_harga_beli_check CHECK ((harga_beli >= (0)::numeric)),
    CONSTRAINT products_harga_jual_check CHECK ((harga_jual >= (0)::numeric)),
    CONSTRAINT products_stok_minimum_check CHECK ((stok_minimum >= 0))
);


ALTER TABLE public.products OWNER TO postgres;

--
-- Name: TABLE products; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.products IS 'Katalog produk baju koko';


--
-- Name: COLUMN products.sku; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.products.sku IS 'Stock Keeping Unit â€” kode unik per produk';


--
-- Name: COLUMN products.harga_beli; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.products.harga_beli IS 'Harga modal / harga beli dari supplier';


--
-- Name: COLUMN products.harga_jual; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.products.harga_jual IS 'Harga jual ke pelanggan';


--
-- Name: COLUMN products.stok_minimum; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.products.stok_minimum IS 'Batas bawah stok sebelum notifikasi muncul';


--
-- Name: COLUMN products.is_active; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.products.is_active IS 'FALSE = produk diarsipkan (soft delete)';


--
-- Name: products_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.products_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.products_id_seq OWNER TO postgres;

--
-- Name: products_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.products_id_seq OWNED BY public.products.id;


--
-- Name: stock; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.stock (
    id integer NOT NULL,
    product_id integer NOT NULL,
    warehouse_id integer NOT NULL,
    quantity integer DEFAULT 0 NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL,
    CONSTRAINT stock_quantity_check CHECK ((quantity >= 0))
);


ALTER TABLE public.stock OWNER TO postgres;

--
-- Name: TABLE stock; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.stock IS 'Jumlah stok setiap produk di setiap gudang';


--
-- Name: COLUMN stock.quantity; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.stock.quantity IS 'Jumlah unit yang tersedia saat ini';


--
-- Name: stock_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.stock_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.stock_id_seq OWNER TO postgres;

--
-- Name: stock_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.stock_id_seq OWNED BY public.stock.id;


--
-- Name: stock_movements; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.stock_movements (
    id integer NOT NULL,
    product_id integer NOT NULL,
    warehouse_id integer,
    from_warehouse_id integer,
    to_warehouse_id integer,
    quantity integer NOT NULL,
    type character varying(20) NOT NULL,
    reference_id integer,
    notes text,
    created_by integer,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    CONSTRAINT stock_movements_quantity_check CHECK (((quantity <> 0) OR ((type)::text = 'koreksi'::text))),
    CONSTRAINT stock_movements_type_check CHECK (((type)::text = ANY ((ARRAY['masuk'::character varying, 'keluar'::character varying, 'transfer'::character varying, 'penjualan'::character varying, 'koreksi'::character varying])::text[])))
);


ALTER TABLE public.stock_movements OWNER TO postgres;

--
-- Name: TABLE stock_movements; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.stock_movements IS 'Riwayat semua pergerakan stok';


--
-- Name: COLUMN stock_movements.type; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.stock_movements.type IS 'masuk=restok, keluar=pengurangan manual, transfer=pindah gudang, penjualan=dari kasir, koreksi=penyesuaian';


--
-- Name: COLUMN stock_movements.reference_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.stock_movements.reference_id IS 'ID dari tabel transactions jika berasal dari penjualan';


--
-- Name: stock_movements_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.stock_movements_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.stock_movements_id_seq OWNER TO postgres;

--
-- Name: stock_movements_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.stock_movements_id_seq OWNED BY public.stock_movements.id;


--
-- Name: transaction_items; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.transaction_items (
    id integer NOT NULL,
    transaction_id integer NOT NULL,
    product_id integer NOT NULL,
    quantity integer NOT NULL,
    harga_jual numeric(12,2) NOT NULL,
    harga_beli numeric(12,2) NOT NULL,
    subtotal numeric(12,2) NOT NULL,
    CONSTRAINT transaction_items_harga_beli_check CHECK ((harga_beli >= (0)::numeric)),
    CONSTRAINT transaction_items_harga_jual_check CHECK ((harga_jual >= (0)::numeric)),
    CONSTRAINT transaction_items_quantity_check CHECK ((quantity > 0)),
    CONSTRAINT transaction_items_subtotal_check CHECK ((subtotal >= (0)::numeric))
);


ALTER TABLE public.transaction_items OWNER TO postgres;

--
-- Name: TABLE transaction_items; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.transaction_items IS 'Detail produk dalam setiap transaksi';


--
-- Name: COLUMN transaction_items.harga_jual; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.transaction_items.harga_jual IS 'Snapshot harga jual saat transaksi terjadi (bukan dari products)';


--
-- Name: COLUMN transaction_items.harga_beli; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.transaction_items.harga_beli IS 'Snapshot HPP untuk kalkulasi laba rugi';


--
-- Name: transaction_items_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.transaction_items_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.transaction_items_id_seq OWNER TO postgres;

--
-- Name: transaction_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.transaction_items_id_seq OWNED BY public.transaction_items.id;


--
-- Name: transactions; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.transactions (
    id integer NOT NULL,
    transaction_code character varying(50) NOT NULL,
    cashier_id integer,
    warehouse_id integer,
    total_amount numeric(12,2) DEFAULT 0 NOT NULL,
    payment_method character varying(10) NOT NULL,
    amount_paid numeric(12,2) DEFAULT 0,
    change_amount numeric(12,2) DEFAULT 0,
    midtrans_order_id character varying(100),
    payment_status character varying(10) DEFAULT 'pending'::character varying NOT NULL,
    notes text,
    transaction_date timestamp without time zone DEFAULT now() NOT NULL,
    discount_amount numeric(12,2) DEFAULT 0,
    CONSTRAINT transactions_discount_amount_check CHECK ((discount_amount >= (0)::numeric)),
    CONSTRAINT transactions_payment_method_check CHECK (((payment_method)::text = ANY ((ARRAY['tunai'::character varying, 'qris'::character varying])::text[]))),
    CONSTRAINT transactions_payment_status_check CHECK (((payment_status)::text = ANY ((ARRAY['pending'::character varying, 'paid'::character varying, 'failed'::character varying, 'expired'::character varying])::text[]))),
    CONSTRAINT transactions_total_amount_check CHECK ((total_amount >= (0)::numeric))
);


ALTER TABLE public.transactions OWNER TO postgres;

--
-- Name: TABLE transactions; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.transactions IS 'Header transaksi penjualan';


--
-- Name: COLUMN transactions.transaction_code; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.transactions.transaction_code IS 'Format: TRX-YYYYMMDD-XXXX';


--
-- Name: COLUMN transactions.warehouse_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.transactions.warehouse_id IS 'Gudang yang stoknya dikurangi';


--
-- Name: COLUMN transactions.midtrans_order_id; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.transactions.midtrans_order_id IS 'Diisi saat payment_method = qris';


--
-- Name: transactions_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.transactions_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.transactions_id_seq OWNER TO postgres;

--
-- Name: transactions_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.transactions_id_seq OWNED BY public.transactions.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id integer NOT NULL,
    name character varying(100) NOT NULL,
    email character varying(150) NOT NULL,
    password_hash character varying(255) NOT NULL,
    role character varying(20) DEFAULT 'kasir'::character varying NOT NULL,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL,
    updated_at timestamp without time zone DEFAULT now() NOT NULL,
    CONSTRAINT users_role_check CHECK (((role)::text = ANY ((ARRAY['pemilik'::character varying, 'kasir'::character varying])::text[])))
);


ALTER TABLE public.users OWNER TO postgres;

--
-- Name: TABLE users; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.users IS 'Pengguna aplikasi: pemilik bisnis dan kasir';


--
-- Name: COLUMN users.role; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.users.role IS 'pemilik = akses penuh, kasir = akses terbatas';


--
-- Name: COLUMN users.is_active; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON COLUMN public.users.is_active IS 'FALSE = akun dinonaktifkan (soft delete)';


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.users_id_seq
    AS integer
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
-- Name: warehouses; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.warehouses (
    id integer NOT NULL,
    name character varying(100) NOT NULL,
    location character varying(200) NOT NULL,
    description text,
    is_active boolean DEFAULT true NOT NULL,
    created_at timestamp without time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.warehouses OWNER TO postgres;

--
-- Name: TABLE warehouses; Type: COMMENT; Schema: public; Owner: postgres
--

COMMENT ON TABLE public.warehouses IS 'Lokasi penyimpanan stok (bisa lebih dari satu gudang)';


--
-- Name: warehouses_id_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.warehouses_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.warehouses_id_seq OWNER TO postgres;

--
-- Name: warehouses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.warehouses_id_seq OWNED BY public.warehouses.id;


--
-- Name: products id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.products ALTER COLUMN id SET DEFAULT nextval('public.products_id_seq'::regclass);


--
-- Name: stock id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.stock ALTER COLUMN id SET DEFAULT nextval('public.stock_id_seq'::regclass);


--
-- Name: stock_movements id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.stock_movements ALTER COLUMN id SET DEFAULT nextval('public.stock_movements_id_seq'::regclass);


--
-- Name: transaction_items id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transaction_items ALTER COLUMN id SET DEFAULT nextval('public.transaction_items_id_seq'::regclass);


--
-- Name: transactions id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions ALTER COLUMN id SET DEFAULT nextval('public.transactions_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: warehouses id; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.warehouses ALTER COLUMN id SET DEFAULT nextval('public.warehouses_id_seq'::regclass);


--
-- Data for Name: products; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.products VALUES (3, 'Baju Koko Putih Polos - L', 'BK-PUTIH-L', 'Polos', 65000.00, 95000.00, 10, 'Baju koko putih polos ukuran L', true, '2026-03-07 01:11:35.336734', '2026-03-07 01:11:35.336734');
INSERT INTO public.products VALUES (4, 'Baju Koko Putih Polos - XL', 'BK-PUTIH-XL', 'Polos', 68000.00, 98000.00, 8, 'Baju koko putih polos ukuran XL', true, '2026-03-07 01:11:35.336734', '2026-03-07 01:11:35.336734');
INSERT INTO public.products VALUES (5, 'Baju Koko Batik Biru - M', 'BK-BATIK-BM', 'Batik', 85000.00, 130000.00, 5, 'Baju koko motif batik warna biru ukuran M', true, '2026-03-07 01:11:35.336734', '2026-03-07 01:11:35.336734');
INSERT INTO public.products VALUES (6, 'Baju Koko Batik Biru - L', 'BK-BATIK-BL', 'Batik', 85000.00, 130000.00, 5, 'Baju koko motif batik warna biru ukuran L', true, '2026-03-07 01:11:35.336734', '2026-03-07 01:11:35.336734');
INSERT INTO public.products VALUES (7, 'Baju Koko Batik Coklat - M', 'BK-BATIK-CM', 'Batik', 87000.00, 135000.00, 5, 'Baju koko motif batik warna coklat ukuran M', true, '2026-03-07 01:11:35.336734', '2026-03-07 01:11:35.336734');
INSERT INTO public.products VALUES (8, 'Baju Koko Bordir Putih - L', 'BK-BORDIR-L', 'Bordir', 95000.00, 150000.00, 3, 'Baju koko bordir khas ukuran L', true, '2026-03-07 01:11:35.336734', '2026-03-07 01:11:35.336734');
INSERT INTO public.products VALUES (9, 'Baju Koko Bordir Putih - XL', 'BK-BORDIR-XL', 'Bordir', 98000.00, 155000.00, 3, 'Baju koko bordir khas ukuran XL', true, '2026-03-07 01:11:35.336734', '2026-03-07 01:11:35.336734');
INSERT INTO public.products VALUES (1, 'Baju Koko Putih Polos - S', 'BK-PUTIH-S', 'Polos', 65000.00, 95000.00, 10, 'Baju koko putih polos ukuran S', false, '2026-03-07 01:11:35.336734', '2026-04-05 15:30:01.363132');
INSERT INTO public.products VALUES (2, 'Baju Koko Putih Polos - M', 'BK-PUTIH-M', 'Polos', 65000.00, 95000.00, 11, 'Baju koko putih polos ukuran M', true, '2026-03-07 01:11:35.336734', '2026-03-09 02:58:45.442655');
INSERT INTO public.products VALUES (12, 'Sajadah Gajah Duduk Abu-abu - XXL', 'SAJ-GAJ-0001-13', 'Sajadah', 170000.00, 220000.00, 8, 'Produk Sajadah berkualitas dari merk Gajah Duduk warna Abu-abu ukuran XXL.', true, '2026-03-13 20:50:44.898589', '2026-03-13 20:50:44.898589');
INSERT INTO public.products VALUES (13, 'Koko Gajah Duduk Coklat - All Size', 'KOK-GAJ-0002-70', 'Koko', 100000.00, 145000.00, 3, 'Produk Koko berkualitas dari merk Gajah Duduk warna Coklat ukuran All Size.', true, '2026-03-13 20:50:44.903423', '2026-03-13 20:50:44.903423');
INSERT INTO public.products VALUES (14, 'Sarung Elzatta Hitam - M', 'SAR-ELZ-0003-84', 'Sarung', 180000.00, 205000.00, 15, 'Produk Sarung berkualitas dari merk Elzatta warna Hitam ukuran M.', true, '2026-03-13 20:50:44.904677', '2026-03-13 20:50:44.904677');
INSERT INTO public.products VALUES (15, 'Sorban Gajah Duduk Hitam - S', 'SOR-GAJ-0004-90', 'Sorban', 50000.00, 85000.00, 9, 'Produk Sorban berkualitas dari merk Gajah Duduk warna Hitam ukuran S.', true, '2026-03-13 20:50:44.90521', '2026-03-13 20:50:44.90521');
INSERT INTO public.products VALUES (16, 'Mukena Dian Pelangi Putih - L', 'MUK-DIA-0005-68', 'Mukena', 150000.00, 195000.00, 4, 'Produk Mukena berkualitas dari merk Dian Pelangi warna Putih ukuran L.', true, '2026-03-13 20:50:44.905578', '2026-03-13 20:50:44.905578');
INSERT INTO public.products VALUES (17, 'Sorban Wadimor Coklat - All Size', 'SOR-WAD-0006-48', 'Sorban', 280000.00, 315000.00, 15, 'Produk Sorban berkualitas dari merk Wadimor warna Coklat ukuran All Size.', true, '2026-03-13 20:50:44.905915', '2026-03-13 20:50:44.905915');
INSERT INTO public.products VALUES (18, 'Batik Atlas Kuning - XL', 'BAT-ATL-0007-23', 'Batik', 190000.00, 220000.00, 11, 'Produk Batik berkualitas dari merk Atlas warna Kuning ukuran XL.', true, '2026-03-13 20:50:44.906363', '2026-03-13 20:50:44.906363');
INSERT INTO public.products VALUES (19, 'Mukena BHS Merah - M', 'MUK-BHS-0008-60', 'Mukena', 260000.00, 275000.00, 14, 'Produk Mukena berkualitas dari merk BHS warna Merah ukuran M.', true, '2026-03-13 20:50:44.906752', '2026-03-13 20:50:44.906752');
INSERT INTO public.products VALUES (20, 'Koko Rabbani Hijau - S', 'KOK-RAB-0009-10', 'Koko', 230000.00, 275000.00, 11, 'Produk Koko berkualitas dari merk Rabbani warna Hijau ukuran S.', true, '2026-03-13 20:50:44.908014', '2026-03-13 20:50:44.908014');
INSERT INTO public.products VALUES (21, 'Pakaian Anak Elzatta Merah - S', 'PAK-ELZ-0010-97', 'Pakaian Anak', 100000.00, 150000.00, 10, 'Produk Pakaian Anak berkualitas dari merk Elzatta warna Merah ukuran S.', true, '2026-03-13 20:50:44.908461', '2026-03-13 20:50:44.908461');
INSERT INTO public.products VALUES (22, 'Koko Mangga Ungu - L', 'KOK-MAN-0011-39', 'Koko', 250000.00, 260000.00, 11, 'Produk Koko berkualitas dari merk Mangga warna Ungu ukuran L.', true, '2026-03-13 20:50:44.908896', '2026-03-13 20:50:44.908896');
INSERT INTO public.products VALUES (23, 'Koko Rabbani Biru - XL', 'KOK-RAB-0012-87', 'Koko', 240000.00, 255000.00, 13, 'Produk Koko berkualitas dari merk Rabbani warna Biru ukuran XL.', true, '2026-03-13 20:50:44.90957', '2026-03-13 20:50:44.90957');
INSERT INTO public.products VALUES (24, 'Sorban Atlas Orange - XXL', 'SOR-ATL-0013-89', 'Sorban', 30000.00, 45000.00, 13, 'Produk Sorban berkualitas dari merk Atlas warna Orange ukuran XXL.', true, '2026-03-13 20:50:44.910289', '2026-03-13 20:50:44.910289');
INSERT INTO public.products VALUES (25, 'Mukena Gajah Duduk Merah - S', 'MUK-GAJ-0014-84', 'Mukena', 190000.00, 235000.00, 9, 'Produk Mukena berkualitas dari merk Gajah Duduk warna Merah ukuran S.', true, '2026-03-13 20:50:44.910779', '2026-03-13 20:50:44.910779');
INSERT INTO public.products VALUES (26, 'Sarung Atlas Hijau - All Size', 'SAR-ATL-0015-17', 'Sarung', 240000.00, 270000.00, 11, 'Produk Sarung berkualitas dari merk Atlas warna Hijau ukuran All Size.', true, '2026-03-13 20:50:44.911345', '2026-03-13 20:50:44.911345');
INSERT INTO public.products VALUES (27, 'Peci Rabbani Merah - S', 'PEC-RAB-0016-81', 'Peci', 220000.00, 245000.00, 6, 'Produk Peci berkualitas dari merk Rabbani warna Merah ukuran S.', true, '2026-03-13 20:50:44.911859', '2026-03-13 20:50:44.911859');
INSERT INTO public.products VALUES (28, 'Sorban Dian Pelangi Coklat - S', 'SOR-DIA-0017-25', 'Sorban', 300000.00, 340000.00, 8, 'Produk Sorban berkualitas dari merk Dian Pelangi warna Coklat ukuran S.', true, '2026-03-13 20:50:44.91229', '2026-03-13 20:50:44.91229');
INSERT INTO public.products VALUES (29, 'Sajadah Rabbani Coklat - L', 'SAJ-RAB-0018-70', 'Sajadah', 200000.00, 215000.00, 7, 'Produk Sajadah berkualitas dari merk Rabbani warna Coklat ukuran L.', true, '2026-03-13 20:50:44.91273', '2026-03-13 20:50:44.91273');
INSERT INTO public.products VALUES (30, 'Mukena Wadimor Merah - XL', 'MUK-WAD-0019-94', 'Mukena', 140000.00, 160000.00, 7, 'Produk Mukena berkualitas dari merk Wadimor warna Merah ukuran XL.', true, '2026-03-13 20:50:44.913082', '2026-03-13 20:50:44.913082');
INSERT INTO public.products VALUES (31, 'Peci BHS Hitam - All Size', 'PEC-BHS-0020-29', 'Peci', 270000.00, 280000.00, 4, 'Produk Peci berkualitas dari merk BHS warna Hitam ukuran All Size.', true, '2026-03-13 20:50:44.913406', '2026-03-13 20:50:44.913406');
INSERT INTO public.products VALUES (32, 'Koko BHS Ungu - L', 'KOK-BHS-0021-26', 'Koko', 230000.00, 240000.00, 14, 'Produk Koko berkualitas dari merk BHS warna Ungu ukuran L.', true, '2026-03-13 20:50:44.913713', '2026-03-13 20:50:44.913713');
INSERT INTO public.products VALUES (33, 'Sorban Dian Pelangi Orange - M', 'SOR-DIA-0022-23', 'Sorban', 200000.00, 250000.00, 14, 'Produk Sorban berkualitas dari merk Dian Pelangi warna Orange ukuran M.', true, '2026-03-13 20:50:44.914068', '2026-03-13 20:50:44.914068');
INSERT INTO public.products VALUES (34, 'Koko Gajah Duduk Putih - XL', 'KOK-GAJ-0023-89', 'Koko', 170000.00, 220000.00, 11, 'Produk Koko berkualitas dari merk Gajah Duduk warna Putih ukuran XL.', true, '2026-03-13 20:50:44.914418', '2026-03-13 20:50:44.914418');
INSERT INTO public.products VALUES (35, 'Mukena Dian Pelangi Hijau - S', 'MUK-DIA-0024-78', 'Mukena', 60000.00, 95000.00, 4, 'Produk Mukena berkualitas dari merk Dian Pelangi warna Hijau ukuran S.', true, '2026-03-13 20:50:44.914759', '2026-03-13 20:50:44.914759');
INSERT INTO public.products VALUES (36, 'Batik Zoya Abu-abu - M', 'BAT-ZOY-0025-30', 'Batik', 180000.00, 230000.00, 3, 'Produk Batik berkualitas dari merk Zoya warna Abu-abu ukuran M.', true, '2026-03-13 20:50:44.915081', '2026-03-13 20:50:44.915081');
INSERT INTO public.products VALUES (37, 'Pakaian Anak Rabbani Ungu - XL', 'PAK-RAB-0026-46', 'Pakaian Anak', 240000.00, 260000.00, 5, 'Produk Pakaian Anak berkualitas dari merk Rabbani warna Ungu ukuran XL.', true, '2026-03-13 20:50:44.915902', '2026-03-13 20:50:44.915902');
INSERT INTO public.products VALUES (38, 'Sorban Wadimor Hijau - XL', 'SOR-WAD-0027-86', 'Sorban', 120000.00, 160000.00, 13, 'Produk Sorban berkualitas dari merk Wadimor warna Hijau ukuran XL.', true, '2026-03-13 20:50:44.91683', '2026-03-13 20:50:44.91683');
INSERT INTO public.products VALUES (39, 'Gamis Atlas Coklat - M', 'GAM-ATL-0028-67', 'Gamis', 220000.00, 245000.00, 4, 'Produk Gamis berkualitas dari merk Atlas warna Coklat ukuran M.', true, '2026-03-13 20:50:44.91795', '2026-03-13 20:50:44.91795');
INSERT INTO public.products VALUES (40, 'Gamis Rabbani Kuning - S', 'GAM-RAB-0029-55', 'Gamis', 80000.00, 130000.00, 9, 'Produk Gamis berkualitas dari merk Rabbani warna Kuning ukuran S.', true, '2026-03-13 20:50:44.91927', '2026-03-13 20:50:44.91927');
INSERT INTO public.products VALUES (41, 'Sorban Wadimor Merah - L', 'SOR-WAD-0030-70', 'Sorban', 70000.00, 110000.00, 4, 'Produk Sorban berkualitas dari merk Wadimor warna Merah ukuran L.', true, '2026-03-13 20:50:44.919998', '2026-03-13 20:50:44.919998');
INSERT INTO public.products VALUES (42, 'Pakaian Anak Rabbani Merah - XL', 'PAK-RAB-0031-90', 'Pakaian Anak', 210000.00, 255000.00, 12, 'Produk Pakaian Anak berkualitas dari merk Rabbani warna Merah ukuran XL.', true, '2026-03-13 20:50:44.920496', '2026-03-13 20:50:44.920496');
INSERT INTO public.products VALUES (43, 'Sajadah Dian Pelangi Kuning - XL', 'SAJ-DIA-0032-29', 'Sajadah', 120000.00, 130000.00, 5, 'Produk Sajadah berkualitas dari merk Dian Pelangi warna Kuning ukuran XL.', true, '2026-03-13 20:50:44.920893', '2026-03-13 20:50:44.920893');
INSERT INTO public.products VALUES (44, 'Sorban Rabbani Hijau - L', 'SOR-RAB-0033-87', 'Sorban', 60000.00, 90000.00, 4, 'Produk Sorban berkualitas dari merk Rabbani warna Hijau ukuran L.', true, '2026-03-13 20:50:44.92163', '2026-03-13 20:50:44.92163');
INSERT INTO public.products VALUES (10, 'Baju Koko Anak - 8 Tahun', 'BK-ANAK-8', 'Anak', 45000.00, 70000.00, 8, 'Baju koko anak usia 8 tahun', false, '2026-03-07 01:11:35.336734', '2026-04-04 23:41:04.537474');
INSERT INTO public.products VALUES (11, 'Baju Koko Orange Polos - M', 'BK-ORANGE-M', 'Polos', 65000.00, 95000.00, 5, NULL, false, '2026-03-09 02:59:47.70056', '2026-04-05 11:31:21.967571');
INSERT INTO public.products VALUES (45, 'Mukena Wadimor Hitam - XXL', 'MUK-WAD-0034-27', 'Mukena', 250000.00, 255000.00, 4, 'Produk Mukena berkualitas dari merk Wadimor warna Hitam ukuran XXL.', true, '2026-03-13 20:50:44.922325', '2026-03-13 20:50:44.922325');
INSERT INTO public.products VALUES (46, 'Sajadah Wadimor Hitam - M', 'SAJ-WAD-0035-96', 'Sajadah', 40000.00, 65000.00, 13, 'Produk Sajadah berkualitas dari merk Wadimor warna Hitam ukuran M.', true, '2026-03-13 20:50:44.925644', '2026-03-13 20:50:44.925644');
INSERT INTO public.products VALUES (47, 'Koko Zoya Biru - M', 'KOK-ZOY-0036-73', 'Koko', 220000.00, 260000.00, 11, 'Produk Koko berkualitas dari merk Zoya warna Biru ukuran M.', true, '2026-03-13 20:50:44.926342', '2026-03-13 20:50:44.926342');
INSERT INTO public.products VALUES (48, 'Mukena Gajah Duduk Putih - M', 'MUK-GAJ-0037-87', 'Mukena', 280000.00, 295000.00, 5, 'Produk Mukena berkualitas dari merk Gajah Duduk warna Putih ukuran M.', true, '2026-03-13 20:50:44.927227', '2026-03-13 20:50:44.927227');
INSERT INTO public.products VALUES (49, 'Sarung Rabbani Orange - M', 'SAR-RAB-0038-55', 'Sarung', 220000.00, 225000.00, 4, 'Produk Sarung berkualitas dari merk Rabbani warna Orange ukuran M.', true, '2026-03-13 20:50:44.927631', '2026-03-13 20:50:44.927631');
INSERT INTO public.products VALUES (50, 'Peci Mangga Putih - S', 'PEC-MAN-0039-90', 'Peci', 290000.00, 340000.00, 14, 'Produk Peci berkualitas dari merk Mangga warna Putih ukuran S.', true, '2026-03-13 20:50:44.928002', '2026-03-13 20:50:44.928002');
INSERT INTO public.products VALUES (51, 'Sorban Elzatta Coklat - L', 'SOR-ELZ-0040-21', 'Sorban', 270000.00, 305000.00, 4, 'Produk Sorban berkualitas dari merk Elzatta warna Coklat ukuran L.', true, '2026-03-13 20:50:44.928376', '2026-03-13 20:50:44.928376');
INSERT INTO public.products VALUES (52, 'Pakaian Anak Rabbani Ungu - XL', 'PAK-RAB-0041-29', 'Pakaian Anak', 50000.00, 90000.00, 7, 'Produk Pakaian Anak berkualitas dari merk Rabbani warna Ungu ukuran XL.', true, '2026-03-13 20:50:44.928724', '2026-03-13 20:50:44.928724');
INSERT INTO public.products VALUES (53, 'Koko Wadimor Putih - L', 'KOK-WAD-0042-45', 'Koko', 130000.00, 175000.00, 9, 'Produk Koko berkualitas dari merk Wadimor warna Putih ukuran L.', true, '2026-03-13 20:50:44.929045', '2026-03-13 20:50:44.929045');
INSERT INTO public.products VALUES (54, 'Sajadah Mangga Putih - XXL', 'SAJ-MAN-0043-49', 'Sajadah', 200000.00, 215000.00, 14, 'Produk Sajadah berkualitas dari merk Mangga warna Putih ukuran XXL.', true, '2026-03-13 20:50:44.929357', '2026-03-13 20:50:44.929357');
INSERT INTO public.products VALUES (55, 'Gamis Gajah Duduk Orange - XXL', 'GAM-GAJ-0044-82', 'Gamis', 280000.00, 285000.00, 14, 'Produk Gamis berkualitas dari merk Gajah Duduk warna Orange ukuran XXL.', true, '2026-03-13 20:50:44.929683', '2026-03-13 20:50:44.929683');
INSERT INTO public.products VALUES (56, 'Koko Gajah Duduk Ungu - L', 'KOK-GAJ-0045-70', 'Koko', 260000.00, 280000.00, 12, 'Produk Koko berkualitas dari merk Gajah Duduk warna Ungu ukuran L.', true, '2026-03-13 20:50:44.930012', '2026-03-13 20:50:44.930012');
INSERT INTO public.products VALUES (57, 'Sorban Zoya Coklat - L', 'SOR-ZOY-0046-81', 'Sorban', 50000.00, 55000.00, 12, 'Produk Sorban berkualitas dari merk Zoya warna Coklat ukuran L.', true, '2026-03-13 20:50:44.930431', '2026-03-13 20:50:44.930431');
INSERT INTO public.products VALUES (58, 'Batik Zoya Putih - S', 'BAT-ZOY-0047-43', 'Batik', 260000.00, 280000.00, 4, 'Produk Batik berkualitas dari merk Zoya warna Putih ukuran S.', true, '2026-03-13 20:50:44.930858', '2026-03-13 20:50:44.930858');
INSERT INTO public.products VALUES (59, 'Pakaian Anak Elzatta Hijau - XL', 'PAK-ELZ-0048-23', 'Pakaian Anak', 220000.00, 260000.00, 11, 'Produk Pakaian Anak berkualitas dari merk Elzatta warna Hijau ukuran XL.', true, '2026-03-13 20:50:44.931264', '2026-03-13 20:50:44.931264');
INSERT INTO public.products VALUES (60, 'Koko Elzatta Ungu - L', 'KOK-ELZ-0049-83', 'Koko', 100000.00, 140000.00, 8, 'Produk Koko berkualitas dari merk Elzatta warna Ungu ukuran L.', true, '2026-03-13 20:50:44.931731', '2026-03-13 20:50:44.931731');
INSERT INTO public.products VALUES (61, 'Sajadah Mangga Coklat - M', 'SAJ-MAN-0050-81', 'Sajadah', 190000.00, 220000.00, 12, 'Produk Sajadah berkualitas dari merk Mangga warna Coklat ukuran M.', true, '2026-03-13 20:50:44.932653', '2026-03-13 20:50:44.932653');
INSERT INTO public.products VALUES (62, 'Koko Dian Pelangi Merah - S', 'KOK-DIA-0051-67', 'Koko', 250000.00, 265000.00, 4, 'Produk Koko berkualitas dari merk Dian Pelangi warna Merah ukuran S.', true, '2026-03-13 20:50:44.933011', '2026-03-13 20:50:44.933011');
INSERT INTO public.products VALUES (63, 'Sorban Rabbani Orange - XXL', 'SOR-RAB-0052-64', 'Sorban', 210000.00, 230000.00, 5, 'Produk Sorban berkualitas dari merk Rabbani warna Orange ukuran XXL.', true, '2026-03-13 20:50:44.933345', '2026-03-13 20:50:44.933345');
INSERT INTO public.products VALUES (64, 'Mukena Elzatta Hijau - All Size', 'MUK-ELZ-0053-31', 'Mukena', 150000.00, 200000.00, 10, 'Produk Mukena berkualitas dari merk Elzatta warna Hijau ukuran All Size.', true, '2026-03-13 20:50:44.933684', '2026-03-13 20:50:44.933684');
INSERT INTO public.products VALUES (65, 'Pakaian Anak Atlas Biru - XXL', 'PAK-ATL-0054-45', 'Pakaian Anak', 270000.00, 290000.00, 10, 'Produk Pakaian Anak berkualitas dari merk Atlas warna Biru ukuran XXL.', true, '2026-03-13 20:50:44.934009', '2026-03-13 20:50:44.934009');
INSERT INTO public.products VALUES (66, 'Sajadah Rabbani Merah - M', 'SAJ-RAB-0055-91', 'Sajadah', 130000.00, 180000.00, 6, 'Produk Sajadah berkualitas dari merk Rabbani warna Merah ukuran M.', true, '2026-03-13 20:50:44.934323', '2026-03-13 20:50:44.934323');
INSERT INTO public.products VALUES (67, 'Pakaian Anak Zoya Orange - L', 'PAK-ZOY-0056-51', 'Pakaian Anak', 280000.00, 330000.00, 3, 'Produk Pakaian Anak berkualitas dari merk Zoya warna Orange ukuran L.', true, '2026-03-13 20:50:44.934659', '2026-03-13 20:50:44.934659');
INSERT INTO public.products VALUES (68, 'Sorban Rabbani Biru - L', 'SOR-RAB-0057-35', 'Sorban', 210000.00, 235000.00, 15, 'Produk Sorban berkualitas dari merk Rabbani warna Biru ukuran L.', true, '2026-03-13 20:50:44.934985', '2026-03-13 20:50:44.934985');
INSERT INTO public.products VALUES (69, 'Sorban Mangga Coklat - XL', 'SOR-MAN-0058-69', 'Sorban', 230000.00, 260000.00, 5, 'Produk Sorban berkualitas dari merk Mangga warna Coklat ukuran XL.', true, '2026-03-13 20:50:44.935332', '2026-03-13 20:50:44.935332');
INSERT INTO public.products VALUES (70, 'Sorban Elzatta Kuning - All Size', 'SOR-ELZ-0059-36', 'Sorban', 230000.00, 265000.00, 11, 'Produk Sorban berkualitas dari merk Elzatta warna Kuning ukuran All Size.', true, '2026-03-13 20:50:44.935644', '2026-03-13 20:50:44.935644');
INSERT INTO public.products VALUES (71, 'Sorban Atlas Merah - S', 'SOR-ATL-0060-91', 'Sorban', 40000.00, 75000.00, 5, 'Produk Sorban berkualitas dari merk Atlas warna Merah ukuran S.', true, '2026-03-13 20:50:44.935957', '2026-03-13 20:50:44.935957');
INSERT INTO public.products VALUES (72, 'Mukena Zoya Ungu - XL', 'MUK-ZOY-0061-61', 'Mukena', 80000.00, 90000.00, 10, 'Produk Mukena berkualitas dari merk Zoya warna Ungu ukuran XL.', true, '2026-03-13 20:50:44.936277', '2026-03-13 20:50:44.936277');
INSERT INTO public.products VALUES (73, 'Sorban Elzatta Hitam - S', 'SOR-ELZ-0062-88', 'Sorban', 30000.00, 35000.00, 10, 'Produk Sorban berkualitas dari merk Elzatta warna Hitam ukuran S.', true, '2026-03-13 20:50:44.936672', '2026-03-13 20:50:44.936672');
INSERT INTO public.products VALUES (74, 'Gamis BHS Abu-abu - All Size', 'GAM-BHS-0063-61', 'Gamis', 170000.00, 180000.00, 4, 'Produk Gamis berkualitas dari merk BHS warna Abu-abu ukuran All Size.', true, '2026-03-13 20:50:44.937041', '2026-03-13 20:50:44.937041');
INSERT INTO public.products VALUES (75, 'Sajadah Mangga Putih - S', 'SAJ-MAN-0064-24', 'Sajadah', 110000.00, 115000.00, 8, 'Produk Sajadah berkualitas dari merk Mangga warna Putih ukuran S.', true, '2026-03-13 20:50:44.937676', '2026-03-13 20:50:44.937676');
INSERT INTO public.products VALUES (76, 'Gamis Mangga Biru - S', 'GAM-MAN-0065-10', 'Gamis', 80000.00, 85000.00, 8, 'Produk Gamis berkualitas dari merk Mangga warna Biru ukuran S.', true, '2026-03-13 20:50:44.938569', '2026-03-13 20:50:44.938569');
INSERT INTO public.products VALUES (77, 'Sarung BHS Abu-abu - L', 'SAR-BHS-0066-20', 'Sarung', 50000.00, 85000.00, 8, 'Produk Sarung berkualitas dari merk BHS warna Abu-abu ukuran L.', true, '2026-03-13 20:50:44.93899', '2026-03-13 20:50:44.93899');
INSERT INTO public.products VALUES (78, 'Sarung Gajah Duduk Hitam - XL', 'SAR-GAJ-0067-48', 'Sarung', 270000.00, 280000.00, 12, 'Produk Sarung berkualitas dari merk Gajah Duduk warna Hitam ukuran XL.', true, '2026-03-13 20:50:44.939332', '2026-03-13 20:50:44.939332');
INSERT INTO public.products VALUES (79, 'Batik BHS Abu-abu - XL', 'BAT-BHS-0068-12', 'Batik', 160000.00, 200000.00, 8, 'Produk Batik berkualitas dari merk BHS warna Abu-abu ukuran XL.', true, '2026-03-13 20:50:44.939798', '2026-03-13 20:50:44.939798');
INSERT INTO public.products VALUES (80, 'Pakaian Anak Dian Pelangi Orange - S', 'PAK-DIA-0069-26', 'Pakaian Anak', 270000.00, 320000.00, 9, 'Produk Pakaian Anak berkualitas dari merk Dian Pelangi warna Orange ukuran S.', true, '2026-03-13 20:50:44.940129', '2026-03-13 20:50:44.940129');
INSERT INTO public.products VALUES (81, 'Sarung Gajah Duduk Putih - S', 'SAR-GAJ-0070-37', 'Sarung', 100000.00, 130000.00, 12, 'Produk Sarung berkualitas dari merk Gajah Duduk warna Putih ukuran S.', true, '2026-03-13 20:50:44.940459', '2026-03-13 20:50:44.940459');
INSERT INTO public.products VALUES (82, 'Sorban Elzatta Orange - S', 'SOR-ELZ-0071-38', 'Sorban', 260000.00, 305000.00, 10, 'Produk Sorban berkualitas dari merk Elzatta warna Orange ukuran S.', true, '2026-03-13 20:50:44.940787', '2026-03-13 20:50:44.940787');
INSERT INTO public.products VALUES (83, 'Sarung Rabbani Hitam - All Size', 'SAR-RAB-0072-13', 'Sarung', 300000.00, 350000.00, 11, 'Produk Sarung berkualitas dari merk Rabbani warna Hitam ukuran All Size.', true, '2026-03-13 20:50:44.941145', '2026-03-13 20:50:44.941145');
INSERT INTO public.products VALUES (84, 'Gamis Rabbani Merah - All Size', 'GAM-RAB-0073-78', 'Gamis', 40000.00, 70000.00, 15, 'Produk Gamis berkualitas dari merk Rabbani warna Merah ukuran All Size.', true, '2026-03-13 20:50:44.941561', '2026-03-13 20:50:44.941561');
INSERT INTO public.products VALUES (85, 'Gamis BHS Biru - XL', 'GAM-BHS-0074-63', 'Gamis', 160000.00, 175000.00, 4, 'Produk Gamis berkualitas dari merk BHS warna Biru ukuran XL.', true, '2026-03-13 20:50:44.941987', '2026-03-13 20:50:44.941987');
INSERT INTO public.products VALUES (86, 'Peci BHS Hijau - L', 'PEC-BHS-0075-73', 'Peci', 110000.00, 155000.00, 6, 'Produk Peci berkualitas dari merk BHS warna Hijau ukuran L.', true, '2026-03-13 20:50:44.94233', '2026-03-13 20:50:44.94233');
INSERT INTO public.products VALUES (87, 'Pakaian Anak Atlas Biru - XXL', 'PAK-ATL-0076-50', 'Pakaian Anak', 210000.00, 220000.00, 5, 'Produk Pakaian Anak berkualitas dari merk Atlas warna Biru ukuran XXL.', true, '2026-03-13 20:50:44.942646', '2026-03-13 20:50:44.942646');
INSERT INTO public.products VALUES (88, 'Pakaian Anak Gajah Duduk Orange - All Size', 'PAK-GAJ-0077-82', 'Pakaian Anak', 300000.00, 335000.00, 12, 'Produk Pakaian Anak berkualitas dari merk Gajah Duduk warna Orange ukuran All Size.', true, '2026-03-13 20:50:44.942977', '2026-03-13 20:50:44.942977');
INSERT INTO public.products VALUES (89, 'Sarung Rabbani Hijau - XL', 'SAR-RAB-0078-46', 'Sarung', 70000.00, 85000.00, 8, 'Produk Sarung berkualitas dari merk Rabbani warna Hijau ukuran XL.', true, '2026-03-13 20:50:44.945003', '2026-03-13 20:50:44.945003');
INSERT INTO public.products VALUES (90, 'Koko Wadimor Hijau - XL', 'KOK-WAD-0079-61', 'Koko', 40000.00, 75000.00, 3, 'Produk Koko berkualitas dari merk Wadimor warna Hijau ukuran XL.', true, '2026-03-13 20:50:44.945561', '2026-03-13 20:50:44.945561');
INSERT INTO public.products VALUES (91, 'Koko Wadimor Hijau - L', 'KOK-WAD-0080-83', 'Koko', 220000.00, 225000.00, 15, 'Produk Koko berkualitas dari merk Wadimor warna Hijau ukuran L.', true, '2026-03-13 20:50:44.945964', '2026-03-13 20:50:44.945964');
INSERT INTO public.products VALUES (92, 'Gamis Zoya Merah - XL', 'GAM-ZOY-0081-42', 'Gamis', 180000.00, 215000.00, 8, 'Produk Gamis berkualitas dari merk Zoya warna Merah ukuran XL.', true, '2026-03-13 20:50:44.946375', '2026-03-13 20:50:44.946375');
INSERT INTO public.products VALUES (93, 'Peci Gajah Duduk Biru - XL', 'PEC-GAJ-0082-50', 'Peci', 270000.00, 310000.00, 9, 'Produk Peci berkualitas dari merk Gajah Duduk warna Biru ukuran XL.', true, '2026-03-13 20:50:44.946918', '2026-03-13 20:50:44.946918');
INSERT INTO public.products VALUES (94, 'Gamis Rabbani Hitam - L', 'GAM-RAB-0083-70', 'Gamis', 140000.00, 145000.00, 15, 'Produk Gamis berkualitas dari merk Rabbani warna Hitam ukuran L.', true, '2026-03-13 20:50:44.947285', '2026-03-13 20:50:44.947285');
INSERT INTO public.products VALUES (95, 'Sarung Atlas Putih - XXL', 'SAR-ATL-0084-93', 'Sarung', 110000.00, 155000.00, 10, 'Produk Sarung berkualitas dari merk Atlas warna Putih ukuran XXL.', true, '2026-03-13 20:50:44.947854', '2026-03-13 20:50:44.947854');
INSERT INTO public.products VALUES (96, 'Sorban Wadimor Abu-abu - S', 'SOR-WAD-0085-24', 'Sorban', 260000.00, 295000.00, 10, 'Produk Sorban berkualitas dari merk Wadimor warna Abu-abu ukuran S.', true, '2026-03-13 20:50:44.948569', '2026-03-13 20:50:44.948569');
INSERT INTO public.products VALUES (97, 'Sarung Wadimor Coklat - S', 'SAR-WAD-0086-29', 'Sarung', 290000.00, 310000.00, 10, 'Produk Sarung berkualitas dari merk Wadimor warna Coklat ukuran S.', true, '2026-03-13 20:50:44.948961', '2026-03-13 20:50:44.948961');
INSERT INTO public.products VALUES (98, 'Koko Mangga Hijau - XXL', 'KOK-MAN-0087-67', 'Koko', 300000.00, 330000.00, 3, 'Produk Koko berkualitas dari merk Mangga warna Hijau ukuran XXL.', true, '2026-03-13 20:50:44.949301', '2026-03-13 20:50:44.949301');
INSERT INTO public.products VALUES (99, 'Sarung Atlas Coklat - M', 'SAR-ATL-0088-23', 'Sarung', 200000.00, 245000.00, 7, 'Produk Sarung berkualitas dari merk Atlas warna Coklat ukuran M.', true, '2026-03-13 20:50:44.949649', '2026-03-13 20:50:44.949649');
INSERT INTO public.products VALUES (100, 'Peci Zoya Hitam - All Size', 'PEC-ZOY-0089-23', 'Peci', 170000.00, 210000.00, 13, 'Produk Peci berkualitas dari merk Zoya warna Hitam ukuran All Size.', true, '2026-03-13 20:50:44.95047', '2026-03-13 20:50:44.95047');
INSERT INTO public.products VALUES (101, 'Sarung Gajah Duduk Biru - M', 'SAR-GAJ-0090-63', 'Sarung', 230000.00, 260000.00, 8, 'Produk Sarung berkualitas dari merk Gajah Duduk warna Biru ukuran M.', true, '2026-03-13 20:50:44.95112', '2026-03-13 20:50:44.95112');
INSERT INTO public.products VALUES (102, 'Sarung Rabbani Abu-abu - M', 'SAR-RAB-0091-37', 'Sarung', 70000.00, 95000.00, 14, 'Produk Sarung berkualitas dari merk Rabbani warna Abu-abu ukuran M.', true, '2026-03-13 20:50:44.951744', '2026-03-13 20:50:44.951744');
INSERT INTO public.products VALUES (103, 'Gamis Dian Pelangi Abu-abu - S', 'GAM-DIA-0092-11', 'Gamis', 30000.00, 60000.00, 10, 'Produk Gamis berkualitas dari merk Dian Pelangi warna Abu-abu ukuran S.', true, '2026-03-13 20:50:44.952143', '2026-03-13 20:50:44.952143');
INSERT INTO public.products VALUES (104, 'Sajadah Wadimor Abu-abu - M', 'SAJ-WAD-0093-42', 'Sajadah', 250000.00, 285000.00, 3, 'Produk Sajadah berkualitas dari merk Wadimor warna Abu-abu ukuran M.', true, '2026-03-13 20:50:44.952489', '2026-03-13 20:50:44.952489');
INSERT INTO public.products VALUES (105, 'Gamis BHS Kuning - XXL', 'GAM-BHS-0094-62', 'Gamis', 60000.00, 110000.00, 5, 'Produk Gamis berkualitas dari merk BHS warna Kuning ukuran XXL.', true, '2026-03-13 20:50:44.952868', '2026-03-13 20:50:44.952868');
INSERT INTO public.products VALUES (106, 'Pakaian Anak Atlas Putih - XL', 'PAK-ATL-0095-50', 'Pakaian Anak', 280000.00, 285000.00, 13, 'Produk Pakaian Anak berkualitas dari merk Atlas warna Putih ukuran XL.', true, '2026-03-13 20:50:44.953208', '2026-03-13 20:50:44.953208');
INSERT INTO public.products VALUES (107, 'Peci Dian Pelangi Merah - All Size', 'PEC-DIA-0096-41', 'Peci', 190000.00, 225000.00, 3, 'Produk Peci berkualitas dari merk Dian Pelangi warna Merah ukuran All Size.', true, '2026-03-13 20:50:44.953572', '2026-03-13 20:50:44.953572');
INSERT INTO public.products VALUES (108, 'Batik Atlas Hitam - L', 'BAT-ATL-0097-59', 'Batik', 60000.00, 70000.00, 7, 'Produk Batik berkualitas dari merk Atlas warna Hitam ukuran L.', true, '2026-03-13 20:50:44.953953', '2026-03-13 20:50:44.953953');
INSERT INTO public.products VALUES (109, 'Sorban Elzatta Putih - All Size', 'SOR-ELZ-0098-47', 'Sorban', 170000.00, 175000.00, 3, 'Produk Sorban berkualitas dari merk Elzatta warna Putih ukuran All Size.', true, '2026-03-13 20:50:44.954285', '2026-03-13 20:50:44.954285');
INSERT INTO public.products VALUES (110, 'Pakaian Anak Elzatta Putih - S', 'PAK-ELZ-0099-90', 'Pakaian Anak', 40000.00, 55000.00, 14, 'Produk Pakaian Anak berkualitas dari merk Elzatta warna Putih ukuran S.', true, '2026-03-13 20:50:44.954593', '2026-03-13 20:50:44.954593');
INSERT INTO public.products VALUES (111, 'Batik Mangga Biru - L', 'BAT-MAN-0100-66', 'Batik', 250000.00, 275000.00, 4, 'Produk Batik berkualitas dari merk Mangga warna Biru ukuran L.', true, '2026-03-13 20:50:44.954893', '2026-03-13 20:50:44.954893');
INSERT INTO public.products VALUES (120, 'Al-Qur&#039;an Makka', 'PECI-H-M-01', 'AL-QUR&#039;AN', 50000.00, 90000.00, 5, NULL, true, '2026-04-18 15:56:02.394868', '2026-04-18 15:56:02.394868');
INSERT INTO public.products VALUES (121, 'Jubah kalcer', 'JB-KC-001', 'JUBAH', 95000.00, 200000.00, 10, NULL, true, '2026-06-21 17:21:18.106682', '2026-06-21 17:21:18.106682');
INSERT INTO public.products VALUES (113, 'surban hitam', 'SR-123', 'surban', 30000.00, 50000.00, 7, NULL, false, '2026-04-03 23:55:17.625586', '2026-04-03 23:59:13.420952');
INSERT INTO public.products VALUES (112, 'Kurta Dewasa', 'KT-HTM-M', 'Kurta polos hitam', 85000.00, 150000.00, 9, NULL, true, '2026-03-14 03:36:35.998624', '2026-04-04 23:39:46.927813');
INSERT INTO public.products VALUES (114, 'sajadah anak polos', 'SJ-ANAK-01', 'Anak', 30000.00, 60000.00, 6, 'sajadah bahan katun', false, '2026-04-05 09:46:45.93899', '2026-04-05 09:48:37.948434');
INSERT INTO public.products VALUES (115, 'Jubah Hitam Bordir', 'JUBAH-H-01', 'Jubah', 100000.00, 250000.00, 8, 'kain katun', false, '2026-04-05 11:29:37.396677', '2026-04-05 11:30:45.67006');
INSERT INTO public.products VALUES (116, 'jubah hitam dewasa', 'JUBAH-H-11', 'JUBAH', 100000.00, 250000.00, 5, NULL, true, '2026-04-05 18:28:47.437747', '2026-04-05 18:29:31.526289');
INSERT INTO public.products VALUES (117, 'celana', 'CL-BC-1', 'CELANA', 20000.00, 100000.00, 5, NULL, true, '2026-04-17 01:41:33.993766', '2026-04-17 01:41:33.993766');
INSERT INTO public.products VALUES (118, 'Jubah Jumbo Hitam Sollu', 'JUBAH-J-H-01', 'JUBAH JUMBO', 100000.00, 250000.00, 10, NULL, true, '2026-04-18 15:22:03.143592', '2026-04-18 15:22:03.143592');
INSERT INTO public.products VALUES (119, 'Koko Lengan Panjang Bordir Madina', 'KOKO-LP-01', 'KOKO LP', 100000.00, 150000.00, 10, NULL, true, '2026-04-18 15:49:46.756986', '2026-04-18 15:49:46.756986');


--
-- Data for Name: stock; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.stock VALUES (9, 9, 1, 50, '2026-03-07 01:11:35.336734');
INSERT INTO public.stock VALUES (12, 2, 2, 20, '2026-03-07 01:11:35.336734');
INSERT INTO public.stock VALUES (13, 3, 2, 20, '2026-03-07 01:11:35.336734');
INSERT INTO public.stock VALUES (14, 4, 2, 20, '2026-03-07 01:11:35.336734');
INSERT INTO public.stock VALUES (16, 6, 2, 20, '2026-03-07 01:11:35.336734');
INSERT INTO public.stock VALUES (17, 7, 2, 20, '2026-03-07 01:11:35.336734');
INSERT INTO public.stock VALUES (19, 11, 2, 1, '2026-03-10 01:02:39.146691');
INSERT INTO public.stock VALUES (3, 3, 1, 28, '2026-04-04 00:03:50.183486');
INSERT INTO public.stock VALUES (18, 11, 1, 6, '2026-03-13 20:27:44.922079');
INSERT INTO public.stock VALUES (31, 111, 3, 0, '2026-03-14 03:09:22.951737');
INSERT INTO public.stock VALUES (33, 111, 2, 3, '2026-03-14 03:09:22.951737');
INSERT INTO public.stock VALUES (32, 19, 1, 4, '2026-04-05 12:10:07.173002');
INSERT INTO public.stock VALUES (34, 112, 1, 10, '2026-04-05 12:11:11.103894');
INSERT INTO public.stock VALUES (1, 1, 1, 48, '2026-04-05 15:28:37.32537');
INSERT INTO public.stock VALUES (8, 8, 1, 46, '2026-04-05 18:31:33.58413');
INSERT INTO public.stock VALUES (75, 119, 1, 9, '2026-04-18 15:49:46.770813');
INSERT INTO public.stock VALUES (10, 10, 1, 76, '2026-04-04 23:39:59.071748');
INSERT INTO public.stock VALUES (11, 1, 2, 19, '2026-05-20 15:22:44.369245');
INSERT INTO public.stock VALUES (15, 5, 2, 29, '2026-05-20 15:22:44.369245');
INSERT INTO public.stock VALUES (4, 4, 1, 30, '2026-06-21 17:14:42.836748');
INSERT INTO public.stock VALUES (63, 6, 3, 10, '2026-04-05 09:50:39.101579');
INSERT INTO public.stock VALUES (81, 120, 2, 20, '2026-06-21 17:18:20.600517');
INSERT INTO public.stock VALUES (20, 11, 3, 9, '2026-03-15 18:18:01.090625');
INSERT INTO public.stock VALUES (29, 108, 1, 9, '2026-04-05 10:10:39.662166');
INSERT INTO public.stock VALUES (82, 121, 1, 24, '2026-06-21 17:21:18.132331');
INSERT INTO public.stock VALUES (7, 7, 1, 33, '2026-06-21 17:22:21.909514');
INSERT INTO public.stock VALUES (6, 6, 1, 84, '2026-06-21 17:24:06.866656');
INSERT INTO public.stock VALUES (5, 5, 1, 35, '2026-06-21 17:24:23.9098');
INSERT INTO public.stock VALUES (71, 18, 1, 50, '2026-04-05 11:34:03.719291');
INSERT INTO public.stock VALUES (72, 5, 3, 10, '2026-04-05 11:35:36.331294');
INSERT INTO public.stock VALUES (30, 79, 1, 9, '2026-06-21 21:32:47.595303');
INSERT INTO public.stock VALUES (76, 120, 1, 65, '2026-06-21 21:32:47.595303');
INSERT INTO public.stock VALUES (2, 2, 1, 46, '2026-06-21 22:16:40.783149');


--
-- Data for Name: stock_movements; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.stock_movements VALUES (1, 11, 1, NULL, NULL, 5, 'masuk', NULL, NULL, 1, '2026-03-10 01:02:06.534463');
INSERT INTO public.stock_movements VALUES (2, 11, NULL, 1, 2, 1, 'transfer', NULL, NULL, 1, '2026-03-10 01:02:39.146691');
INSERT INTO public.stock_movements VALUES (3, 11, 3, NULL, NULL, 10, 'masuk', NULL, NULL, 1, '2026-03-10 01:26:45.041802');
INSERT INTO public.stock_movements VALUES (4, 10, 1, NULL, NULL, 4, 'masuk', NULL, NULL, 1, '2026-03-13 20:26:44.22834');
INSERT INTO public.stock_movements VALUES (5, 10, 1, NULL, NULL, 2, 'masuk', NULL, NULL, 1, '2026-03-13 20:26:52.691251');
INSERT INTO public.stock_movements VALUES (6, 10, 1, NULL, NULL, 3, 'masuk', NULL, NULL, 1, '2026-03-13 20:27:10.192533');
INSERT INTO public.stock_movements VALUES (7, 10, 1, NULL, NULL, 2, 'masuk', NULL, NULL, 1, '2026-03-13 20:27:26.907821');
INSERT INTO public.stock_movements VALUES (8, 10, 1, NULL, NULL, 1, 'masuk', NULL, NULL, 1, '2026-03-13 20:27:38.22843');
INSERT INTO public.stock_movements VALUES (9, 11, 1, NULL, NULL, 2, 'masuk', NULL, NULL, 1, '2026-03-13 20:27:44.922079');
INSERT INTO public.stock_movements VALUES (10, 6, 1, NULL, NULL, 1, 'masuk', NULL, NULL, 1, '2026-03-13 20:30:41.666213');
INSERT INTO public.stock_movements VALUES (11, 6, 1, NULL, NULL, 1, 'masuk', NULL, NULL, 1, '2026-03-13 20:30:49.134393');
INSERT INTO public.stock_movements VALUES (12, 108, 1, NULL, NULL, 2, 'masuk', NULL, NULL, 1, '2026-03-13 20:57:39.260184');
INSERT INTO public.stock_movements VALUES (13, 79, 1, NULL, NULL, 1, 'masuk', NULL, NULL, 1, '2026-03-14 02:51:30.275033');
INSERT INTO public.stock_movements VALUES (14, 111, 3, NULL, NULL, 3, 'masuk', NULL, NULL, 1, '2026-03-14 02:55:24.35883');
INSERT INTO public.stock_movements VALUES (15, 19, 1, NULL, NULL, 5, 'masuk', NULL, 'mukena premium', 1, '2026-03-14 02:56:03.28007');
INSERT INTO public.stock_movements VALUES (16, 111, NULL, 3, 2, 3, 'transfer', NULL, NULL, 1, '2026-03-14 03:09:22.951737');
INSERT INTO public.stock_movements VALUES (17, 112, 1, NULL, NULL, 11, 'masuk', NULL, 'Restok dari suplier', 1, '2026-03-14 03:39:43.338576');
INSERT INTO public.stock_movements VALUES (18, 10, 1, NULL, NULL, 2, 'keluar', NULL, NULL, 1, '2026-03-14 03:42:30.878878');
INSERT INTO public.stock_movements VALUES (19, 10, 1, NULL, NULL, 50, 'koreksi', NULL, NULL, 1, '2026-03-14 03:42:41.30157');
INSERT INTO public.stock_movements VALUES (20, 10, 1, NULL, NULL, 60, 'masuk', NULL, NULL, 1, '2026-03-14 03:43:26.946214');
INSERT INTO public.stock_movements VALUES (21, 10, 1, NULL, NULL, 10, 'koreksi', NULL, NULL, 1, '2026-03-14 03:43:40.820947');
INSERT INTO public.stock_movements VALUES (22, 10, 1, NULL, NULL, 1, 'penjualan', 1, 'Penjualan TRX-20260314-0001', 1, '2026-03-14 16:58:25.295778');
INSERT INTO public.stock_movements VALUES (23, 3, 1, NULL, NULL, 1, 'penjualan', 2, 'Penjualan TRX-20260314-0002', 1, '2026-03-14 17:08:59.528075');
INSERT INTO public.stock_movements VALUES (24, 3, 1, NULL, NULL, 1, 'penjualan', 3, 'Penjualan TRX-20260314-0003', 1, '2026-03-14 17:09:08.71491');
INSERT INTO public.stock_movements VALUES (25, 3, 1, NULL, NULL, 1, 'penjualan', 4, 'Penjualan TRX-20260314-0004', 1, '2026-03-14 17:09:21.191361');
INSERT INTO public.stock_movements VALUES (26, 7, 1, NULL, NULL, 1, 'penjualan', 4, 'Penjualan TRX-20260314-0004', 1, '2026-03-14 17:09:21.191361');
INSERT INTO public.stock_movements VALUES (27, 108, 1, NULL, NULL, 1, 'penjualan', 4, 'Penjualan TRX-20260314-0004', 1, '2026-03-14 17:09:21.191361');
INSERT INTO public.stock_movements VALUES (28, 3, 1, NULL, NULL, 1, 'penjualan', 5, 'Penjualan TRX-20260314-0005', 1, '2026-03-14 17:09:23.546007');
INSERT INTO public.stock_movements VALUES (29, 7, 1, NULL, NULL, 1, 'penjualan', 5, 'Penjualan TRX-20260314-0005', 1, '2026-03-14 17:09:23.546007');
INSERT INTO public.stock_movements VALUES (30, 108, 1, NULL, NULL, 1, 'penjualan', 5, 'Penjualan TRX-20260314-0005', 1, '2026-03-14 17:09:23.546007');
INSERT INTO public.stock_movements VALUES (33, 3, 1, NULL, NULL, 1, 'penjualan', 7, 'Penjualan TRX-20260314-0006', 1, '2026-03-14 17:10:45.480726');
INSERT INTO public.stock_movements VALUES (34, 10, 1, NULL, NULL, 1, 'penjualan', 8, 'Penjualan TRX-20260314-0007', 1, '2026-03-14 17:15:27.339197');
INSERT INTO public.stock_movements VALUES (35, 7, 1, NULL, NULL, 1, 'penjualan', 9, 'Penjualan TRX-20260314-0008', 1, '2026-03-14 17:18:34.143309');
INSERT INTO public.stock_movements VALUES (36, 3, 1, NULL, NULL, 1, 'penjualan', 10, 'Penjualan TRX-20260314-0009', 1, '2026-03-14 17:19:40.239733');
INSERT INTO public.stock_movements VALUES (37, 3, 1, NULL, NULL, 1, 'penjualan', 11, 'Penjualan TRX-20260314-0010', 1, '2026-03-14 17:26:27.85182');
INSERT INTO public.stock_movements VALUES (38, 3, 1, NULL, NULL, 2, 'penjualan', 12, 'Penjualan TRX-20260314-0011', 1, '2026-03-14 17:27:08.679275');
INSERT INTO public.stock_movements VALUES (39, 7, 1, NULL, NULL, 1, 'penjualan', 13, 'Penjualan TRX-20260314-0012', 1, '2026-03-14 17:42:59.80009');
INSERT INTO public.stock_movements VALUES (40, 10, 1, NULL, NULL, 1, 'penjualan', 14, 'Penjualan TRX-20260315-0001', 1, '2026-03-15 02:58:08.271222');
INSERT INTO public.stock_movements VALUES (41, 3, 1, NULL, NULL, 1, 'penjualan', 18, 'Penjualan QRIS TRX-20260315-0005', 1, '2026-03-15 04:14:13.971902');
INSERT INTO public.stock_movements VALUES (42, 3, 1, NULL, NULL, 2, 'penjualan', 22, 'Penjualan TRX-20260315-0009', 1, '2026-03-15 04:19:42.879649');
INSERT INTO public.stock_movements VALUES (43, 3, 1, NULL, NULL, 2, 'penjualan', 25, 'Penjualan TRX-20260315-0012', 1, '2026-03-15 04:21:00.709654');
INSERT INTO public.stock_movements VALUES (44, 7, 1, NULL, NULL, 1, 'penjualan', 28, 'Penjualan TRX-20260315-0015', 1, '2026-03-15 04:23:25.075932');
INSERT INTO public.stock_movements VALUES (45, 7, 1, NULL, NULL, 1, 'penjualan', 36, 'Penjualan QRIS TRX-20260315-0023', 2, '2026-03-15 04:31:33.149069');
INSERT INTO public.stock_movements VALUES (46, 5, 1, NULL, NULL, 1, 'penjualan', 39, 'Penjualan QRIS TRX-20260315-0026', 2, '2026-03-15 04:54:40.467983');
INSERT INTO public.stock_movements VALUES (47, 3, 1, NULL, NULL, 1, 'penjualan', 40, 'Penjualan QRIS TRX-20260315-0027', 2, '2026-03-15 05:02:23.672619');
INSERT INTO public.stock_movements VALUES (48, 3, 1, NULL, NULL, 2, 'penjualan', 41, 'Penjualan QRIS TRX-20260315-0028', 2, '2026-03-15 05:02:36.650567');
INSERT INTO public.stock_movements VALUES (49, 6, 1, NULL, NULL, 1, 'masuk', NULL, NULL, 1, '2026-03-15 17:43:21.561786');
INSERT INTO public.stock_movements VALUES (50, 10, 1, NULL, NULL, 2, 'koreksi', NULL, NULL, 1, '2026-03-15 17:43:29.113307');
INSERT INTO public.stock_movements VALUES (51, 11, 3, NULL, NULL, 1, 'penjualan', 42, 'Penjualan TRX-20260315-0029', 1, '2026-03-15 18:18:01.090625');
INSERT INTO public.stock_movements VALUES (52, 3, 1, NULL, NULL, 1, 'penjualan', 44, 'Penjualan TRX-20260316-0002', 2, '2026-03-16 14:19:10.474285');
INSERT INTO public.stock_movements VALUES (53, 3, 1, NULL, NULL, 2, 'penjualan', 47, 'Penjualan QRIS TRX-20260319-0003', 3, '2026-03-19 02:25:23.12646');
INSERT INTO public.stock_movements VALUES (54, 7, 1, NULL, NULL, 1, 'penjualan', 51, 'Penjualan QRIS TRX-20260403-0002', 1, '2026-04-03 22:57:01.234846');
INSERT INTO public.stock_movements VALUES (55, 7, 1, NULL, NULL, 1, 'penjualan', 52, 'Penjualan QRIS TRX-20260403-0003', 1, '2026-04-03 22:58:05.509827');
INSERT INTO public.stock_movements VALUES (56, 7, 1, NULL, NULL, 1, 'penjualan', 54, 'Penjualan QRIS TRX-20260403-0005', 1, '2026-04-03 23:04:00.616412');
INSERT INTO public.stock_movements VALUES (57, 3, 1, NULL, NULL, 1, 'penjualan', 55, 'Penjualan QRIS TRX-20260403-0006', 1, '2026-04-03 23:09:12.456755');
INSERT INTO public.stock_movements VALUES (58, 7, 1, NULL, NULL, 1, 'penjualan', 55, 'Penjualan QRIS TRX-20260403-0006', 1, '2026-04-03 23:09:12.456755');
INSERT INTO public.stock_movements VALUES (59, 3, 1, NULL, NULL, 1, 'penjualan', 56, 'Penjualan QRIS TRX-20260404-0001', 1, '2026-04-04 00:03:50.183486');
INSERT INTO public.stock_movements VALUES (60, 10, 1, NULL, NULL, 1, 'penjualan', 57, 'Penjualan QRIS TRX-20260404-0002', 1, '2026-04-04 00:04:20.483314');
INSERT INTO public.stock_movements VALUES (61, 7, 1, NULL, NULL, 2, 'penjualan', 59, 'Penjualan TRX-20260404-0004', 1, '2026-04-04 00:05:22.557724');
INSERT INTO public.stock_movements VALUES (64, 10, 1, NULL, NULL, -4, 'koreksi', NULL, NULL, 1, '2026-04-04 23:11:15.864955');
INSERT INTO public.stock_movements VALUES (65, 10, 1, NULL, NULL, 4, 'koreksi', NULL, NULL, 1, '2026-04-04 23:11:34.091334');
INSERT INTO public.stock_movements VALUES (66, 10, 1, NULL, NULL, 54, 'masuk', NULL, NULL, 1, '2026-04-04 23:12:29.458732');
INSERT INTO public.stock_movements VALUES (67, 10, 1, NULL, NULL, 0, 'koreksi', NULL, NULL, 1, '2026-04-04 23:12:42.033713');
INSERT INTO public.stock_movements VALUES (68, 10, 1, NULL, NULL, 0, 'koreksi', NULL, NULL, 1, '2026-04-04 23:12:58.430118');
INSERT INTO public.stock_movements VALUES (69, 10, 1, NULL, NULL, 1, 'koreksi', NULL, NULL, 1, '2026-04-04 23:13:16.595238');
INSERT INTO public.stock_movements VALUES (70, 6, 1, NULL, NULL, 1, 'koreksi', NULL, NULL, 1, '2026-04-04 23:22:18.722324');
INSERT INTO public.stock_movements VALUES (71, 6, 1, NULL, NULL, -4, 'koreksi', NULL, NULL, 1, '2026-04-04 23:22:37.560291');
INSERT INTO public.stock_movements VALUES (72, 6, 1, NULL, NULL, 100, 'masuk', NULL, NULL, 1, '2026-04-04 23:23:13.901131');
INSERT INTO public.stock_movements VALUES (73, 6, 1, NULL, NULL, -60, 'koreksi', NULL, NULL, 1, '2026-04-04 23:23:55.131902');
INSERT INTO public.stock_movements VALUES (74, 6, 1, NULL, NULL, 5, 'koreksi', NULL, NULL, 1, '2026-04-04 23:24:27.274713');
INSERT INTO public.stock_movements VALUES (75, 10, 1, NULL, NULL, -29, 'koreksi', NULL, NULL, 1, '2026-04-04 23:24:59.555857');
INSERT INTO public.stock_movements VALUES (76, 5, NULL, 1, 2, 10, 'transfer', NULL, NULL, 1, '2026-04-04 23:27:47.174242');
INSERT INTO public.stock_movements VALUES (77, 10, 1, NULL, NULL, 1, 'masuk', NULL, NULL, 1, '2026-04-04 23:32:53.481103');
INSERT INTO public.stock_movements VALUES (78, 10, 1, NULL, NULL, 1, 'keluar', NULL, NULL, 1, '2026-04-04 23:33:00.920971');
INSERT INTO public.stock_movements VALUES (79, 10, 1, NULL, NULL, -1, 'koreksi', NULL, NULL, 1, '2026-04-04 23:33:16.19696');
INSERT INTO public.stock_movements VALUES (80, 108, 1, NULL, NULL, 10, 'masuk', NULL, NULL, 1, '2026-04-04 23:34:42.685858');
INSERT INTO public.stock_movements VALUES (81, 10, 1, NULL, NULL, 1, 'keluar', NULL, NULL, 1, '2026-04-04 23:35:52.537263');
INSERT INTO public.stock_movements VALUES (82, 10, 1, NULL, NULL, -3, 'koreksi', NULL, NULL, 1, '2026-04-04 23:36:15.225505');
INSERT INTO public.stock_movements VALUES (83, 10, 1, NULL, NULL, 1, 'masuk', NULL, NULL, 1, '2026-04-04 23:39:59.071748');
INSERT INTO public.stock_movements VALUES (84, 8, 1, NULL, NULL, 1, 'penjualan', 60, 'Penjualan QRIS TRX-20260404-0005', 1, '2026-04-04 23:42:52.814769');
INSERT INTO public.stock_movements VALUES (85, 6, 1, NULL, NULL, 1, 'penjualan', 61, 'Penjualan QRIS TRX-20260404-0006', 1, '2026-04-04 23:43:23.217428');
INSERT INTO public.stock_movements VALUES (86, 6, 1, NULL, NULL, 1, 'penjualan', 62, 'Penjualan QRIS TRX-20260404-0007', 1, '2026-04-04 23:43:52.481028');
INSERT INTO public.stock_movements VALUES (87, 6, 1, NULL, NULL, 1, 'penjualan', 63, 'Penjualan QRIS TRX-20260404-0008', 1, '2026-04-04 23:44:31.615419');
INSERT INTO public.stock_movements VALUES (88, 5, 1, NULL, NULL, 1, 'penjualan', 64, 'Penjualan QRIS TRX-20260404-0009', 1, '2026-04-04 23:45:02.575352');
INSERT INTO public.stock_movements VALUES (89, 5, 1, NULL, NULL, 1, 'penjualan', 65, 'Penjualan QRIS TRX-20260404-0010', 1, '2026-04-04 23:45:48.680602');
INSERT INTO public.stock_movements VALUES (90, 6, 1, NULL, NULL, 1, 'penjualan', 66, 'Penjualan QRIS TRX-20260404-0011', 1, '2026-04-04 23:46:40.412784');
INSERT INTO public.stock_movements VALUES (91, 6, 1, NULL, NULL, 1, 'penjualan', 67, 'Penjualan QRIS TRX-20260404-0012', 1, '2026-04-04 23:47:28.201096');
INSERT INTO public.stock_movements VALUES (92, 6, NULL, 1, 3, 10, 'transfer', NULL, NULL, 1, '2026-04-05 09:50:39.101579');
INSERT INTO public.stock_movements VALUES (93, 108, 1, NULL, NULL, 1, 'penjualan', 68, 'Penjualan TRX-20260405-0001', 2, '2026-04-05 10:10:39.662166');
INSERT INTO public.stock_movements VALUES (94, 79, 1, NULL, NULL, 10, 'masuk', NULL, NULL, 4, '2026-04-05 10:20:23.945793');
INSERT INTO public.stock_movements VALUES (95, 6, 1, NULL, NULL, 1, 'penjualan', 69, 'Penjualan QRIS TRX-20260405-0002', 4, '2026-04-05 10:22:48.189841');
INSERT INTO public.stock_movements VALUES (96, 6, 1, NULL, NULL, 10, 'masuk', NULL, NULL, 4, '2026-04-05 11:31:54.432802');
INSERT INTO public.stock_movements VALUES (97, 5, 1, NULL, NULL, 10, 'masuk', NULL, NULL, 4, '2026-04-05 11:32:01.525104');
INSERT INTO public.stock_movements VALUES (98, 5, 1, NULL, NULL, 1, 'keluar', NULL, NULL, 4, '2026-04-05 11:32:30.303295');
INSERT INTO public.stock_movements VALUES (99, 5, 1, NULL, NULL, 4, 'koreksi', NULL, NULL, 4, '2026-04-05 11:32:41.079239');
INSERT INTO public.stock_movements VALUES (100, 5, 1, NULL, NULL, -2, 'koreksi', NULL, NULL, 4, '2026-04-05 11:33:25.92914');
INSERT INTO public.stock_movements VALUES (101, 6, 1, NULL, NULL, -10, 'koreksi', NULL, NULL, 4, '2026-04-05 11:33:49.02246');
INSERT INTO public.stock_movements VALUES (102, 18, 1, NULL, NULL, 50, 'koreksi', NULL, NULL, 4, '2026-04-05 11:34:03.719291');
INSERT INTO public.stock_movements VALUES (103, 5, NULL, 1, 3, 10, 'transfer', NULL, 'transfer stok ke toko b', 4, '2026-04-05 11:35:36.331294');
INSERT INTO public.stock_movements VALUES (104, 1, 1, NULL, NULL, 1, 'penjualan', 70, 'Penjualan QRIS TRX-20260405-0003', 4, '2026-04-05 11:36:58.396825');
INSERT INTO public.stock_movements VALUES (105, 6, 1, NULL, NULL, 1, 'penjualan', 70, 'Penjualan QRIS TRX-20260405-0003', 4, '2026-04-05 11:36:58.396825');
INSERT INTO public.stock_movements VALUES (106, 1, 1, NULL, NULL, 1, 'penjualan', 70, 'Penjualan QRIS TRX-20260405-0003', 4, '2026-04-05 11:36:58.793386');
INSERT INTO public.stock_movements VALUES (107, 6, 1, NULL, NULL, 1, 'penjualan', 70, 'Penjualan QRIS TRX-20260405-0003', 4, '2026-04-05 11:36:58.793386');
INSERT INTO public.stock_movements VALUES (108, 6, 1, NULL, NULL, 1, 'penjualan', 71, 'Penjualan QRIS TRX-20260405-0004', 4, '2026-04-05 11:37:31.774689');
INSERT INTO public.stock_movements VALUES (109, 6, 1, NULL, NULL, 1, 'penjualan', 71, 'Penjualan QRIS TRX-20260405-0004', 4, '2026-04-05 11:37:32.066538');
INSERT INTO public.stock_movements VALUES (110, 6, 1, NULL, NULL, 1, 'penjualan', 72, 'Penjualan QRIS TRX-20260405-0005', 4, '2026-04-05 11:38:09.141291');
INSERT INTO public.stock_movements VALUES (111, 6, 1, NULL, NULL, 1, 'penjualan', 72, 'Penjualan QRIS TRX-20260405-0005', 4, '2026-04-05 11:38:09.700756');
INSERT INTO public.stock_movements VALUES (112, 5, 1, NULL, NULL, 1, 'penjualan', 73, 'Penjualan QRIS TRX-20260405-0006', 4, '2026-04-05 11:40:59.133494');
INSERT INTO public.stock_movements VALUES (113, 5, 1, NULL, NULL, 1, 'penjualan', 73, 'Penjualan QRIS TRX-20260405-0006', 4, '2026-04-05 11:40:59.805117');
INSERT INTO public.stock_movements VALUES (114, 6, 1, NULL, NULL, 2, 'penjualan', 74, 'Penjualan QRIS TRX-20260405-0007', 4, '2026-04-05 11:42:42.840718');
INSERT INTO public.stock_movements VALUES (115, 6, 1, NULL, NULL, 1, 'penjualan', 75, 'Penjualan QRIS TRX-20260405-0008', 4, '2026-04-05 11:43:05.654444');
INSERT INTO public.stock_movements VALUES (116, 6, 1, NULL, NULL, 1, 'penjualan', 76, 'Penjualan QRIS TRX-20260405-0009', 2, '2026-04-05 11:44:21.726133');
INSERT INTO public.stock_movements VALUES (117, 8, 1, NULL, NULL, 1, 'penjualan', 77, 'Penjualan QRIS TRX-20260405-0010', 4, '2026-04-05 11:44:49.012124');
INSERT INTO public.stock_movements VALUES (118, 6, 1, NULL, NULL, 1, 'penjualan', 78, 'Penjualan QRIS TRX-20260405-0011', 2, '2026-04-05 11:46:35.624196');
INSERT INTO public.stock_movements VALUES (119, 6, 1, NULL, NULL, 1, 'penjualan', 79, 'Penjualan QRIS TRX-20260405-0012', 2, '2026-04-05 11:49:26.259252');
INSERT INTO public.stock_movements VALUES (120, 6, 1, NULL, NULL, 1, 'penjualan', 80, 'Penjualan QRIS TRX-20260405-0013', 4, '2026-04-05 11:50:04.537432');
INSERT INTO public.stock_movements VALUES (121, 19, 1, NULL, NULL, 1, 'penjualan', 81, 'Penjualan TRX-20260405-0014', 4, '2026-04-05 12:10:07.173002');
INSERT INTO public.stock_movements VALUES (122, 112, 1, NULL, NULL, 1, 'penjualan', 82, 'Penjualan TRX-20260405-0015', 4, '2026-04-05 12:11:11.103894');
INSERT INTO public.stock_movements VALUES (123, 79, 1, NULL, NULL, 1, 'penjualan', 83, 'Penjualan TRX-20260405-0016', 4, '2026-04-05 12:16:57.311458');
INSERT INTO public.stock_movements VALUES (124, 6, 1, NULL, NULL, 1, 'penjualan', 84, 'Penjualan TRX-20260405-0017', 2, '2026-04-05 12:18:20.531708');
INSERT INTO public.stock_movements VALUES (125, 1, 1, NULL, NULL, 1, 'penjualan', 85, 'Penjualan QRIS TRX-20260405-0018', 1, '2026-04-05 14:25:05.041156');
INSERT INTO public.stock_movements VALUES (126, 1, 1, NULL, NULL, 1, 'masuk', NULL, NULL, 1, '2026-04-05 15:28:37.32537');
INSERT INTO public.stock_movements VALUES (127, 6, 1, NULL, NULL, 21, 'masuk', NULL, NULL, 1, '2026-04-05 18:30:39.61041');
INSERT INTO public.stock_movements VALUES (128, 8, 1, NULL, NULL, 2, 'penjualan', 86, 'Penjualan TRX-20260405-0019', 1, '2026-04-05 18:31:33.58413');
INSERT INTO public.stock_movements VALUES (129, 4, 1, NULL, NULL, 50, 'penjualan', 87, 'Penjualan QRIS TRX-20260405-0020', 1, '2026-04-05 18:33:59.788533');
INSERT INTO public.stock_movements VALUES (130, 119, 1, NULL, NULL, 9, 'masuk', NULL, 'Stok awal saat pembuatan produk', 1, '2026-04-18 15:49:46.770813');
INSERT INTO public.stock_movements VALUES (131, 120, 1, NULL, NULL, 100, 'masuk', NULL, 'Stok awal saat pembuatan produk', 1, '2026-04-18 15:56:02.402251');
INSERT INTO public.stock_movements VALUES (132, 7, 1, NULL, NULL, 1, 'penjualan', 88, 'Penjualan QRIS TRX-20260419-0001', 1, '2026-04-19 15:09:33.017155');
INSERT INTO public.stock_movements VALUES (133, 7, 1, NULL, NULL, 1, 'penjualan', 88, 'Penjualan QRIS TRX-20260419-0001', 1, '2026-04-19 15:09:34.229894');
INSERT INTO public.stock_movements VALUES (134, 7, 1, NULL, NULL, 1, 'penjualan', 89, 'Penjualan QRIS TRX-20260419-0002', 1, '2026-04-19 18:53:17.327002');
INSERT INTO public.stock_movements VALUES (135, 2, 1, NULL, NULL, 1, 'penjualan', 90, 'Penjualan TRX-20260419-0003', 1, '2026-04-19 18:53:43.62273');
INSERT INTO public.stock_movements VALUES (136, 1, 2, NULL, NULL, -1, 'penjualan', 1, 'Penjualan kasir Tunai', 2, '2026-05-20 15:22:44.369245');
INSERT INTO public.stock_movements VALUES (137, 5, 2, NULL, NULL, -1, 'penjualan', 1, 'Penjualan kasir Tunai', 2, '2026-05-20 15:22:44.369245');
INSERT INTO public.stock_movements VALUES (138, 2, 1, NULL, NULL, 1, 'penjualan', 92, 'Penjualan QRIS TRX-20260620-0001', 1, '2026-06-20 01:39:26.468773');
INSERT INTO public.stock_movements VALUES (139, 4, 1, NULL, NULL, 30, 'masuk', NULL, NULL, 1, '2026-06-21 17:14:42.836748');
INSERT INTO public.stock_movements VALUES (140, 120, 1, NULL, NULL, 20, 'keluar', NULL, NULL, 1, '2026-06-21 17:15:05.04992');
INSERT INTO public.stock_movements VALUES (141, 120, 1, NULL, NULL, -5, 'koreksi', NULL, NULL, 1, '2026-06-21 17:15:24.393057');
INSERT INTO public.stock_movements VALUES (142, 120, 1, NULL, NULL, 15, 'koreksi', NULL, NULL, 1, '2026-06-21 17:15:36.666503');
INSERT INTO public.stock_movements VALUES (143, 120, NULL, 1, 2, 20, 'transfer', NULL, NULL, 1, '2026-06-21 17:18:20.600517');
INSERT INTO public.stock_movements VALUES (144, 121, 1, NULL, NULL, 24, 'masuk', NULL, 'Stok awal saat pembuatan produk', 1, '2026-06-21 17:21:18.132331');
INSERT INTO public.stock_movements VALUES (145, 7, 1, NULL, NULL, 2, 'penjualan', 93, 'Penjualan TRX-20260621-0001', 1, '2026-06-21 17:22:21.909514');
INSERT INTO public.stock_movements VALUES (146, 120, 1, NULL, NULL, 1, 'penjualan', 94, 'Penjualan QRIS TRX-20260621-0002', 1, '2026-06-21 17:23:29.108694');
INSERT INTO public.stock_movements VALUES (147, 120, 1, NULL, NULL, 1, 'penjualan', 95, 'Penjualan QRIS TRX-20260621-0003', 1, '2026-06-21 17:24:06.498636');
INSERT INTO public.stock_movements VALUES (148, 6, 1, NULL, NULL, 1, 'penjualan', 95, 'Penjualan QRIS TRX-20260621-0003', 1, '2026-06-21 17:24:06.498636');
INSERT INTO public.stock_movements VALUES (149, 120, 1, NULL, NULL, 1, 'penjualan', 95, 'Penjualan QRIS TRX-20260621-0003', 1, '2026-06-21 17:24:06.866656');
INSERT INTO public.stock_movements VALUES (150, 6, 1, NULL, NULL, 1, 'penjualan', 95, 'Penjualan QRIS TRX-20260621-0003', 1, '2026-06-21 17:24:06.866656');
INSERT INTO public.stock_movements VALUES (151, 5, 1, NULL, NULL, 1, 'penjualan', 96, 'Penjualan TRX-20260621-0004', 1, '2026-06-21 17:24:23.9098');
INSERT INTO public.stock_movements VALUES (152, 120, 1, NULL, NULL, 1, 'penjualan', 97, 'Penjualan QRIS TRX-20260621-0005', 1, '2026-06-21 20:16:22.103855');
INSERT INTO public.stock_movements VALUES (153, 79, 1, NULL, NULL, 1, 'penjualan', 100, 'Penjualan QRIS TRX-20260621-0008', 1, '2026-06-21 21:32:47.595303');
INSERT INTO public.stock_movements VALUES (154, 120, 1, NULL, NULL, 1, 'penjualan', 100, 'Penjualan QRIS TRX-20260621-0008', 1, '2026-06-21 21:32:47.595303');
INSERT INTO public.stock_movements VALUES (155, 2, 1, NULL, NULL, 1, 'penjualan', 101, 'Penjualan QRIS TRX-20260621-0009', 1, '2026-06-21 22:16:40.10236');
INSERT INTO public.stock_movements VALUES (156, 2, 1, NULL, NULL, 1, 'penjualan', 101, 'Penjualan QRIS TRX-20260621-0009', 1, '2026-06-21 22:16:40.783149');


--
-- Data for Name: transaction_items; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.transaction_items VALUES (1, 1, 10, 1, 70000.00, 45000.00, 70000.00);
INSERT INTO public.transaction_items VALUES (2, 2, 3, 1, 95000.00, 65000.00, 95000.00);
INSERT INTO public.transaction_items VALUES (3, 3, 3, 1, 95000.00, 65000.00, 95000.00);
INSERT INTO public.transaction_items VALUES (4, 4, 3, 1, 95000.00, 65000.00, 95000.00);
INSERT INTO public.transaction_items VALUES (5, 4, 7, 1, 135000.00, 87000.00, 135000.00);
INSERT INTO public.transaction_items VALUES (6, 4, 108, 1, 70000.00, 60000.00, 70000.00);
INSERT INTO public.transaction_items VALUES (7, 5, 3, 1, 95000.00, 65000.00, 95000.00);
INSERT INTO public.transaction_items VALUES (8, 5, 7, 1, 135000.00, 87000.00, 135000.00);
INSERT INTO public.transaction_items VALUES (9, 5, 108, 1, 70000.00, 60000.00, 70000.00);
INSERT INTO public.transaction_items VALUES (13, 7, 3, 1, 95000.00, 65000.00, 95000.00);
INSERT INTO public.transaction_items VALUES (14, 8, 10, 1, 70000.00, 45000.00, 70000.00);
INSERT INTO public.transaction_items VALUES (15, 9, 7, 1, 135000.00, 87000.00, 135000.00);
INSERT INTO public.transaction_items VALUES (16, 10, 3, 1, 95000.00, 65000.00, 95000.00);
INSERT INTO public.transaction_items VALUES (17, 11, 3, 1, 95000.00, 65000.00, 95000.00);
INSERT INTO public.transaction_items VALUES (18, 12, 3, 2, 95000.00, 65000.00, 190000.00);
INSERT INTO public.transaction_items VALUES (19, 13, 7, 1, 135000.00, 87000.00, 135000.00);
INSERT INTO public.transaction_items VALUES (20, 14, 10, 1, 70000.00, 45000.00, 70000.00);
INSERT INTO public.transaction_items VALUES (21, 15, 2, 1, 95000.00, 65000.00, 95000.00);
INSERT INTO public.transaction_items VALUES (22, 16, 7, 1, 135000.00, 87000.00, 135000.00);
INSERT INTO public.transaction_items VALUES (23, 17, 3, 1, 95000.00, 65000.00, 95000.00);
INSERT INTO public.transaction_items VALUES (24, 18, 3, 1, 95000.00, 65000.00, 95000.00);
INSERT INTO public.transaction_items VALUES (25, 19, 3, 1, 95000.00, 65000.00, 95000.00);
INSERT INTO public.transaction_items VALUES (26, 20, 3, 2, 95000.00, 65000.00, 190000.00);
INSERT INTO public.transaction_items VALUES (27, 20, 79, 1, 200000.00, 160000.00, 200000.00);
INSERT INTO public.transaction_items VALUES (28, 21, 3, 2, 95000.00, 65000.00, 190000.00);
INSERT INTO public.transaction_items VALUES (29, 21, 79, 1, 200000.00, 160000.00, 200000.00);
INSERT INTO public.transaction_items VALUES (30, 22, 3, 2, 95000.00, 65000.00, 190000.00);
INSERT INTO public.transaction_items VALUES (31, 23, 3, 2, 95000.00, 65000.00, 190000.00);
INSERT INTO public.transaction_items VALUES (32, 23, 7, 1, 135000.00, 87000.00, 135000.00);
INSERT INTO public.transaction_items VALUES (33, 24, 3, 1, 95000.00, 65000.00, 95000.00);
INSERT INTO public.transaction_items VALUES (34, 25, 3, 2, 95000.00, 65000.00, 190000.00);
INSERT INTO public.transaction_items VALUES (35, 26, 3, 2, 95000.00, 65000.00, 190000.00);
INSERT INTO public.transaction_items VALUES (36, 26, 79, 1, 200000.00, 160000.00, 200000.00);
INSERT INTO public.transaction_items VALUES (37, 26, 11, 1, 95000.00, 65000.00, 95000.00);
INSERT INTO public.transaction_items VALUES (38, 27, 7, 1, 135000.00, 87000.00, 135000.00);
INSERT INTO public.transaction_items VALUES (39, 28, 7, 1, 135000.00, 87000.00, 135000.00);
INSERT INTO public.transaction_items VALUES (40, 29, 7, 1, 135000.00, 87000.00, 135000.00);
INSERT INTO public.transaction_items VALUES (41, 30, 7, 1, 135000.00, 87000.00, 135000.00);
INSERT INTO public.transaction_items VALUES (42, 31, 10, 1, 70000.00, 45000.00, 70000.00);
INSERT INTO public.transaction_items VALUES (43, 32, 7, 1, 135000.00, 87000.00, 135000.00);
INSERT INTO public.transaction_items VALUES (44, 33, 3, 1, 95000.00, 65000.00, 95000.00);
INSERT INTO public.transaction_items VALUES (45, 34, 7, 1, 135000.00, 87000.00, 135000.00);
INSERT INTO public.transaction_items VALUES (46, 35, 5, 1, 130000.00, 85000.00, 130000.00);
INSERT INTO public.transaction_items VALUES (47, 36, 7, 1, 135000.00, 87000.00, 135000.00);
INSERT INTO public.transaction_items VALUES (48, 37, 7, 1, 135000.00, 87000.00, 135000.00);
INSERT INTO public.transaction_items VALUES (49, 38, 7, 1, 135000.00, 87000.00, 135000.00);
INSERT INTO public.transaction_items VALUES (50, 39, 5, 1, 130000.00, 85000.00, 130000.00);
INSERT INTO public.transaction_items VALUES (51, 40, 3, 1, 95000.00, 65000.00, 95000.00);
INSERT INTO public.transaction_items VALUES (52, 41, 3, 2, 95000.00, 65000.00, 190000.00);
INSERT INTO public.transaction_items VALUES (53, 42, 11, 1, 95000.00, 65000.00, 95000.00);
INSERT INTO public.transaction_items VALUES (54, 43, 10, 1, 70000.00, 45000.00, 70000.00);
INSERT INTO public.transaction_items VALUES (55, 44, 3, 1, 95000.00, 65000.00, 95000.00);
INSERT INTO public.transaction_items VALUES (56, 45, 4, 3, 98000.00, 68000.00, 294000.00);
INSERT INTO public.transaction_items VALUES (57, 46, 3, 2, 95000.00, 65000.00, 190000.00);
INSERT INTO public.transaction_items VALUES (58, 47, 3, 2, 95000.00, 65000.00, 190000.00);
INSERT INTO public.transaction_items VALUES (59, 48, 3, 1, 95000.00, 65000.00, 95000.00);
INSERT INTO public.transaction_items VALUES (60, 49, 7, 1, 135000.00, 87000.00, 135000.00);
INSERT INTO public.transaction_items VALUES (61, 49, 5, 1, 130000.00, 85000.00, 130000.00);
INSERT INTO public.transaction_items VALUES (62, 50, 7, 1, 135000.00, 87000.00, 135000.00);
INSERT INTO public.transaction_items VALUES (63, 51, 7, 1, 135000.00, 87000.00, 135000.00);
INSERT INTO public.transaction_items VALUES (64, 52, 7, 1, 135000.00, 87000.00, 135000.00);
INSERT INTO public.transaction_items VALUES (65, 53, 79, 1, 200000.00, 160000.00, 200000.00);
INSERT INTO public.transaction_items VALUES (66, 53, 3, 1, 95000.00, 65000.00, 95000.00);
INSERT INTO public.transaction_items VALUES (67, 54, 7, 1, 135000.00, 87000.00, 135000.00);
INSERT INTO public.transaction_items VALUES (68, 55, 3, 1, 95000.00, 65000.00, 95000.00);
INSERT INTO public.transaction_items VALUES (69, 55, 7, 1, 135000.00, 87000.00, 135000.00);
INSERT INTO public.transaction_items VALUES (70, 56, 3, 1, 95000.00, 65000.00, 95000.00);
INSERT INTO public.transaction_items VALUES (71, 57, 10, 1, 70000.00, 45000.00, 70000.00);
INSERT INTO public.transaction_items VALUES (72, 58, 7, 1, 135000.00, 87000.00, 135000.00);
INSERT INTO public.transaction_items VALUES (73, 59, 7, 2, 135000.00, 87000.00, 270000.00);
INSERT INTO public.transaction_items VALUES (74, 60, 8, 1, 150000.00, 95000.00, 150000.00);
INSERT INTO public.transaction_items VALUES (75, 61, 6, 1, 130000.00, 85000.00, 130000.00);
INSERT INTO public.transaction_items VALUES (76, 62, 6, 1, 130000.00, 85000.00, 130000.00);
INSERT INTO public.transaction_items VALUES (77, 63, 6, 1, 130000.00, 85000.00, 130000.00);
INSERT INTO public.transaction_items VALUES (78, 64, 5, 1, 130000.00, 85000.00, 130000.00);
INSERT INTO public.transaction_items VALUES (79, 65, 5, 1, 130000.00, 85000.00, 130000.00);
INSERT INTO public.transaction_items VALUES (80, 66, 6, 1, 130000.00, 85000.00, 130000.00);
INSERT INTO public.transaction_items VALUES (81, 67, 6, 1, 130000.00, 85000.00, 130000.00);
INSERT INTO public.transaction_items VALUES (82, 68, 108, 1, 70000.00, 60000.00, 70000.00);
INSERT INTO public.transaction_items VALUES (83, 69, 6, 1, 130000.00, 85000.00, 130000.00);
INSERT INTO public.transaction_items VALUES (84, 70, 1, 1, 95000.00, 65000.00, 95000.00);
INSERT INTO public.transaction_items VALUES (85, 70, 6, 1, 130000.00, 85000.00, 130000.00);
INSERT INTO public.transaction_items VALUES (86, 71, 6, 1, 130000.00, 85000.00, 130000.00);
INSERT INTO public.transaction_items VALUES (87, 72, 6, 1, 130000.00, 85000.00, 130000.00);
INSERT INTO public.transaction_items VALUES (88, 73, 5, 1, 130000.00, 85000.00, 130000.00);
INSERT INTO public.transaction_items VALUES (89, 74, 6, 2, 130000.00, 85000.00, 260000.00);
INSERT INTO public.transaction_items VALUES (90, 75, 6, 1, 130000.00, 85000.00, 130000.00);
INSERT INTO public.transaction_items VALUES (91, 76, 6, 1, 130000.00, 85000.00, 130000.00);
INSERT INTO public.transaction_items VALUES (92, 77, 8, 1, 150000.00, 95000.00, 150000.00);
INSERT INTO public.transaction_items VALUES (93, 78, 6, 1, 130000.00, 85000.00, 130000.00);
INSERT INTO public.transaction_items VALUES (94, 79, 6, 1, 130000.00, 85000.00, 130000.00);
INSERT INTO public.transaction_items VALUES (95, 80, 6, 1, 130000.00, 85000.00, 130000.00);
INSERT INTO public.transaction_items VALUES (96, 81, 19, 1, 275000.00, 260000.00, 275000.00);
INSERT INTO public.transaction_items VALUES (97, 82, 112, 1, 150000.00, 85000.00, 150000.00);
INSERT INTO public.transaction_items VALUES (98, 83, 79, 1, 200000.00, 160000.00, 200000.00);
INSERT INTO public.transaction_items VALUES (99, 84, 6, 1, 130000.00, 85000.00, 130000.00);
INSERT INTO public.transaction_items VALUES (100, 85, 1, 1, 95000.00, 65000.00, 95000.00);
INSERT INTO public.transaction_items VALUES (101, 86, 8, 2, 150000.00, 95000.00, 300000.00);
INSERT INTO public.transaction_items VALUES (102, 87, 4, 50, 98000.00, 68000.00, 4900000.00);
INSERT INTO public.transaction_items VALUES (103, 88, 7, 1, 135000.00, 87000.00, 135000.00);
INSERT INTO public.transaction_items VALUES (104, 89, 7, 1, 135000.00, 87000.00, 135000.00);
INSERT INTO public.transaction_items VALUES (105, 90, 2, 1, 95000.00, 65000.00, 95000.00);
INSERT INTO public.transaction_items VALUES (106, 1, 1, 1, 95000.00, 65000.00, 95000.00);
INSERT INTO public.transaction_items VALUES (107, 1, 5, 1, 130000.00, 85000.00, 130000.00);
INSERT INTO public.transaction_items VALUES (108, 92, 2, 1, 95000.00, 65000.00, 95000.00);
INSERT INTO public.transaction_items VALUES (109, 93, 7, 2, 135000.00, 87000.00, 270000.00);
INSERT INTO public.transaction_items VALUES (110, 94, 120, 1, 90000.00, 50000.00, 90000.00);
INSERT INTO public.transaction_items VALUES (111, 95, 120, 1, 90000.00, 50000.00, 90000.00);
INSERT INTO public.transaction_items VALUES (112, 95, 6, 1, 130000.00, 85000.00, 130000.00);
INSERT INTO public.transaction_items VALUES (113, 96, 5, 1, 130000.00, 85000.00, 130000.00);
INSERT INTO public.transaction_items VALUES (114, 97, 120, 1, 90000.00, 50000.00, 90000.00);
INSERT INTO public.transaction_items VALUES (115, 98, 2, 1, 95000.00, 65000.00, 95000.00);
INSERT INTO public.transaction_items VALUES (116, 99, 2, 1, 95000.00, 65000.00, 95000.00);
INSERT INTO public.transaction_items VALUES (117, 100, 79, 1, 200000.00, 160000.00, 200000.00);
INSERT INTO public.transaction_items VALUES (118, 100, 120, 1, 90000.00, 50000.00, 90000.00);
INSERT INTO public.transaction_items VALUES (119, 101, 2, 1, 95000.00, 65000.00, 95000.00);
INSERT INTO public.transaction_items VALUES (120, 102, 5, 1, 130000.00, 85000.00, 130000.00);
INSERT INTO public.transaction_items VALUES (121, 102, 9, 1, 155000.00, 98000.00, 155000.00);
INSERT INTO public.transaction_items VALUES (122, 102, 119, 1, 150000.00, 100000.00, 150000.00);
INSERT INTO public.transaction_items VALUES (123, 102, 121, 1, 200000.00, 95000.00, 200000.00);
INSERT INTO public.transaction_items VALUES (124, 102, 4, 1, 98000.00, 68000.00, 98000.00);
INSERT INTO public.transaction_items VALUES (125, 102, 112, 1, 150000.00, 85000.00, 150000.00);


--
-- Data for Name: transactions; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.transactions VALUES (1, 'TRX-20260314-0001', 1, 1, 65000.00, 'tunai', 70000.00, 5000.00, NULL, 'paid', '', '2026-03-14 16:58:25.295778', 5000.00);
INSERT INTO public.transactions VALUES (2, 'TRX-20260314-0002', 1, 1, 85000.00, 'tunai', 100000.00, 15000.00, NULL, 'paid', '', '2026-03-14 17:08:59.528075', 10000.00);
INSERT INTO public.transactions VALUES (3, 'TRX-20260314-0003', 1, 1, 85000.00, 'tunai', 100000.00, 15000.00, NULL, 'paid', '', '2026-03-14 17:09:08.71491', 10000.00);
INSERT INTO public.transactions VALUES (4, 'TRX-20260314-0004', 1, 1, 280000.00, 'tunai', 300000.00, 20000.00, NULL, 'paid', '', '2026-03-14 17:09:21.191361', 20000.00);
INSERT INTO public.transactions VALUES (5, 'TRX-20260314-0005', 1, 1, 280000.00, 'tunai', 300000.00, 20000.00, NULL, 'paid', '', '2026-03-14 17:09:23.546007', 20000.00);
INSERT INTO public.transactions VALUES (81, 'TRX-20260405-0014', 4, 1, 225000.00, 'tunai', 300000.00, 75000.00, NULL, 'paid', '', '2026-04-05 12:10:07.173002', 50000.00);
INSERT INTO public.transactions VALUES (7, 'TRX-20260314-0006', 1, 1, 85000.00, 'tunai', 100000.00, 15000.00, NULL, 'paid', '', '2026-03-14 17:10:45.480726', 10000.00);
INSERT INTO public.transactions VALUES (8, 'TRX-20260314-0007', 1, 1, 65000.00, 'tunai', 70000.00, 5000.00, NULL, 'paid', '', '2026-03-14 17:15:27.339197', 5000.00);
INSERT INTO public.transactions VALUES (9, 'TRX-20260314-0008', 1, 1, 125000.00, 'tunai', 150000.00, 25000.00, NULL, 'paid', '', '2026-03-14 17:18:34.143309', 10000.00);
INSERT INTO public.transactions VALUES (10, 'TRX-20260314-0009', 1, 1, 85000.00, 'qris', 85000.00, 0.00, NULL, 'paid', '', '2026-03-14 17:19:40.239733', 10000.00);
INSERT INTO public.transactions VALUES (11, 'TRX-20260314-0010', 1, 1, 95000.00, 'qris', 95000.00, 0.00, NULL, 'paid', '', '2026-03-14 17:26:27.85182', 0.00);
INSERT INTO public.transactions VALUES (12, 'TRX-20260314-0011', 1, 1, 190000.00, 'qris', 190000.00, 0.00, NULL, 'paid', '', '2026-03-14 17:27:08.679275', 0.00);
INSERT INTO public.transactions VALUES (13, 'TRX-20260314-0012', 1, 1, 135000.00, 'qris', 135000.00, 0.00, NULL, 'paid', '', '2026-03-14 17:42:59.80009', 0.00);
INSERT INTO public.transactions VALUES (14, 'TRX-20260315-0001', 1, 1, 60000.00, 'qris', 60000.00, 0.00, NULL, 'paid', '', '2026-03-15 02:58:08.271222', 10000.00);
INSERT INTO public.transactions VALUES (15, 'TRX-20260315-0002', 1, 1, 95000.00, 'qris', 95000.00, 0.00, NULL, 'failed', 'Pembayaran dibatalkan / expired', '2026-03-15 03:56:11.070799', 0.00);
INSERT INTO public.transactions VALUES (18, 'TRX-20260315-0005', 1, 1, 95000.00, 'qris', 95000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-03-15 04:13:32.75687', 0.00);
INSERT INTO public.transactions VALUES (22, 'TRX-20260315-0009', 1, 1, 190000.00, 'qris', 190000.00, 0.00, NULL, 'paid', '', '2026-03-15 04:19:42.879649', 0.00);
INSERT INTO public.transactions VALUES (25, 'TRX-20260315-0012', 1, 1, 190000.00, 'qris', 190000.00, 0.00, NULL, 'paid', '', '2026-03-15 04:21:00.709654', 0.00);
INSERT INTO public.transactions VALUES (28, 'TRX-20260315-0015', 1, 1, 125000.00, 'tunai', 200000.00, 75000.00, NULL, 'paid', '', '2026-03-15 04:23:25.075932', 10000.00);
INSERT INTO public.transactions VALUES (16, 'TRX-20260315-0003', 1, 1, 135000.00, 'qris', 135000.00, 0.00, NULL, 'failed', 'Pembayaran dibatalkan / expired', '2026-03-15 04:11:23.731577', 0.00);
INSERT INTO public.transactions VALUES (17, 'TRX-20260315-0004', 1, 1, 95000.00, 'qris', 95000.00, 0.00, NULL, 'failed', 'Pembayaran dibatalkan / expired', '2026-03-15 04:13:23.627204', 0.00);
INSERT INTO public.transactions VALUES (19, 'TRX-20260315-0006', 1, 1, 95000.00, 'qris', 95000.00, 0.00, NULL, 'failed', 'Pembayaran dibatalkan / expired', '2026-03-15 04:14:49.702237', 0.00);
INSERT INTO public.transactions VALUES (20, 'TRX-20260315-0007', 1, 1, 390000.00, 'qris', 390000.00, 0.00, NULL, 'failed', 'Pembayaran dibatalkan / expired', '2026-03-15 04:15:24.112944', 0.00);
INSERT INTO public.transactions VALUES (36, 'TRX-20260315-0023', 2, 1, 135000.00, 'qris', 135000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-03-15 04:31:16.331928', 0.00);
INSERT INTO public.transactions VALUES (21, 'TRX-20260315-0008', 1, 1, 390000.00, 'qris', 390000.00, 0.00, NULL, 'failed', 'Pembayaran dibatalkan / expired', '2026-03-15 04:15:38.165065', 0.00);
INSERT INTO public.transactions VALUES (23, 'TRX-20260315-0010', 1, 1, 325000.00, 'qris', 325000.00, 0.00, NULL, 'failed', 'Pembayaran dibatalkan / expired', '2026-03-15 04:20:19.890058', 0.00);
INSERT INTO public.transactions VALUES (24, 'TRX-20260315-0011', 1, 1, 95000.00, 'qris', 95000.00, 0.00, NULL, 'failed', 'Pembayaran dibatalkan / expired', '2026-03-15 04:20:47.134621', 0.00);
INSERT INTO public.transactions VALUES (26, 'TRX-20260315-0013', 1, 1, 485000.00, 'qris', 485000.00, 0.00, NULL, 'failed', 'Pembayaran dibatalkan / expired', '2026-03-15 04:21:44.015858', 0.00);
INSERT INTO public.transactions VALUES (27, 'TRX-20260315-0014', 1, 1, 135000.00, 'qris', 135000.00, 0.00, NULL, 'failed', 'Pembayaran dibatalkan / expired', '2026-03-15 04:22:21.556145', 0.00);
INSERT INTO public.transactions VALUES (29, 'TRX-20260315-0016', 1, 1, 135000.00, 'qris', 135000.00, 0.00, NULL, 'failed', 'Pembayaran dibatalkan / expired', '2026-03-15 04:23:48.716319', 0.00);
INSERT INTO public.transactions VALUES (30, 'TRX-20260315-0017', 2, 1, 135000.00, 'qris', 135000.00, 0.00, NULL, 'failed', 'Pembayaran dibatalkan / expired', '2026-03-15 04:24:42.632222', 0.00);
INSERT INTO public.transactions VALUES (31, 'TRX-20260315-0018', 1, 1, 70000.00, 'qris', 70000.00, 0.00, NULL, 'failed', 'Pembayaran dibatalkan / expired', '2026-03-15 04:26:18.204166', 0.00);
INSERT INTO public.transactions VALUES (32, 'TRX-20260315-0019', 1, 1, 135000.00, 'qris', 135000.00, 0.00, NULL, 'failed', 'Pembayaran dibatalkan / expired', '2026-03-15 04:29:24.445301', 0.00);
INSERT INTO public.transactions VALUES (33, 'TRX-20260315-0020', 2, 1, 95000.00, 'qris', 95000.00, 0.00, NULL, 'failed', 'Pembayaran dibatalkan / expired', '2026-03-15 04:30:19.850694', 0.00);
INSERT INTO public.transactions VALUES (34, 'TRX-20260315-0021', 1, 1, 135000.00, 'qris', 135000.00, 0.00, NULL, 'failed', 'Pembayaran dibatalkan / expired', '2026-03-15 04:30:47.808023', 0.00);
INSERT INTO public.transactions VALUES (35, 'TRX-20260315-0022', 2, 1, 130000.00, 'qris', 130000.00, 0.00, NULL, 'failed', 'Pembayaran dibatalkan / expired', '2026-03-15 04:31:02.060894', 0.00);
INSERT INTO public.transactions VALUES (37, 'TRX-20260315-0024', 2, 1, 135000.00, 'qris', 135000.00, 0.00, NULL, 'failed', 'Pembayaran dibatalkan / expired', '2026-03-15 04:31:52.616665', 0.00);
INSERT INTO public.transactions VALUES (39, 'TRX-20260315-0026', 2, 1, 130000.00, 'qris', 130000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-03-15 04:54:07.386059', 0.00);
INSERT INTO public.transactions VALUES (40, 'TRX-20260315-0027', 2, 1, 95000.00, 'qris', 95000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-03-15 04:55:58.804405', 0.00);
INSERT INTO public.transactions VALUES (41, 'TRX-20260315-0028', 2, 1, 190000.00, 'qris', 190000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-03-15 04:57:50.3691', 0.00);
INSERT INTO public.transactions VALUES (38, 'TRX-20260315-0025', 2, 1, 135000.00, 'qris', 135000.00, 0.00, NULL, 'failed', 'Pembayaran dibatalkan / expired', '2026-03-15 04:40:07.453126', 0.00);
INSERT INTO public.transactions VALUES (42, 'TRX-20260315-0029', 1, 3, 95000.00, 'tunai', 150000.00, 55000.00, NULL, 'paid', '', '2026-03-15 18:18:01.090625', 0.00);
INSERT INTO public.transactions VALUES (44, 'TRX-20260316-0002', 2, 1, 85000.00, 'tunai', 100000.00, 15000.00, NULL, 'paid', '', '2026-03-16 14:19:10.474285', 10000.00);
INSERT INTO public.transactions VALUES (43, 'TRX-20260316-0001', 2, 1, 70000.00, 'qris', 70000.00, 0.00, NULL, 'failed', 'Pembayaran dibatalkan / expired', '2026-03-16 14:06:04.582555', 0.00);
INSERT INTO public.transactions VALUES (47, 'TRX-20260319-0003', 3, 1, 190000.00, 'qris', 190000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-03-19 02:24:53.190014', 0.00);
INSERT INTO public.transactions VALUES (45, 'TRX-20260319-0001', 1, 1, 294000.00, 'qris', 294000.00, 0.00, NULL, 'failed', 'Pembayaran dibatalkan / expired', '2026-03-19 02:18:17.295174', 0.00);
INSERT INTO public.transactions VALUES (46, 'TRX-20260319-0002', 1, 1, 190000.00, 'qris', 190000.00, 0.00, NULL, 'failed', 'Pembayaran dibatalkan / expired', '2026-03-19 02:22:57.592307', 0.00);
INSERT INTO public.transactions VALUES (48, 'TRX-20260401-0001', 1, 1, 95000.00, 'qris', 95000.00, 0.00, NULL, 'failed', 'Pembayaran dibatalkan / expired', '2026-04-01 13:26:56.075217', 0.00);
INSERT INTO public.transactions VALUES (49, 'TRX-20260401-0002', 1, 1, 265000.00, 'qris', 265000.00, 0.00, NULL, 'pending', 'Menunggu pembayaran QRIS', '2026-04-01 13:33:22.742764', 0.00);
INSERT INTO public.transactions VALUES (51, 'TRX-20260403-0002', 1, 1, 135000.00, 'qris', 135000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-04-03 22:48:09.903451', 0.00);
INSERT INTO public.transactions VALUES (52, 'TRX-20260403-0003', 1, 1, 135000.00, 'qris', 135000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-04-03 22:57:50.38185', 0.00);
INSERT INTO public.transactions VALUES (50, 'TRX-20260403-0001', 1, 1, 135000.00, 'qris', 135000.00, 0.00, NULL, 'failed', 'Pembayaran dibatalkan / expired', '2026-04-03 22:44:21.02479', 0.00);
INSERT INTO public.transactions VALUES (54, 'TRX-20260403-0005', 1, 1, 135000.00, 'qris', 135000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-04-03 23:03:28.754055', 0.00);
INSERT INTO public.transactions VALUES (55, 'TRX-20260403-0006', 1, 1, 230000.00, 'qris', 230000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-04-03 23:04:40.691299', 0.00);
INSERT INTO public.transactions VALUES (53, 'TRX-20260403-0004', 1, 1, 295000.00, 'qris', 295000.00, 0.00, NULL, 'failed', 'Pembayaran dibatalkan / expired', '2026-04-03 22:59:40.810828', 0.00);
INSERT INTO public.transactions VALUES (56, 'TRX-20260404-0001', 1, 1, 95000.00, 'qris', 95000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-04-04 00:03:23.948902', 0.00);
INSERT INTO public.transactions VALUES (57, 'TRX-20260404-0002', 1, 1, 70000.00, 'qris', 70000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-04-04 00:04:10.844879', 0.00);
INSERT INTO public.transactions VALUES (58, 'TRX-20260404-0003', 1, 1, 135000.00, 'qris', 135000.00, 0.00, NULL, 'failed', 'Pembayaran dibatalkan / expired', '2026-04-04 00:04:31.799067', 0.00);
INSERT INTO public.transactions VALUES (59, 'TRX-20260404-0004', 1, 1, 250000.00, 'tunai', 500000.00, 250000.00, NULL, 'paid', '', '2026-04-04 00:05:22.557724', 20000.00);
INSERT INTO public.transactions VALUES (60, 'TRX-20260404-0005', 1, 1, 150000.00, 'qris', 150000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-04-04 23:42:19.10097', 0.00);
INSERT INTO public.transactions VALUES (61, 'TRX-20260404-0006', 1, 1, 130000.00, 'qris', 130000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-04-04 23:43:11.660913', 0.00);
INSERT INTO public.transactions VALUES (80, 'TRX-20260405-0013', 4, 1, 130000.00, 'qris', 130000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-04-05 11:49:41.693052', 0.00);
INSERT INTO public.transactions VALUES (62, 'TRX-20260404-0007', 1, 1, 130000.00, 'qris', 130000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-04-04 23:43:34.061122', 0.00);
INSERT INTO public.transactions VALUES (82, 'TRX-20260405-0015', 4, 1, 140000.00, 'tunai', 150000.00, 10000.00, NULL, 'paid', '', '2026-04-05 12:11:11.103894', 10000.00);
INSERT INTO public.transactions VALUES (63, 'TRX-20260404-0008', 1, 1, 130000.00, 'qris', 130000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-04-04 23:44:17.039777', 0.00);
INSERT INTO public.transactions VALUES (83, 'TRX-20260405-0016', 4, 1, 200000.00, 'tunai', 200000.00, 0.00, NULL, 'paid', '', '2026-04-05 12:16:57.311458', 0.00);
INSERT INTO public.transactions VALUES (64, 'TRX-20260404-0009', 1, 1, 130000.00, 'qris', 130000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-04-04 23:44:51.764382', 0.00);
INSERT INTO public.transactions VALUES (84, 'TRX-20260405-0017', 2, 1, 130000.00, 'tunai', 150000.00, 20000.00, NULL, 'paid', '', '2026-04-05 12:18:20.531708', 0.00);
INSERT INTO public.transactions VALUES (65, 'TRX-20260404-0010', 1, 1, 130000.00, 'qris', 130000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-04-04 23:45:38.015775', 0.00);
INSERT INTO public.transactions VALUES (66, 'TRX-20260404-0011', 1, 1, 130000.00, 'qris', 130000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-04-04 23:46:26.258098', 0.00);
INSERT INTO public.transactions VALUES (85, 'TRX-20260405-0018', 1, 1, 95000.00, 'qris', 95000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-04-05 14:21:55.359563', 0.00);
INSERT INTO public.transactions VALUES (67, 'TRX-20260404-0012', 1, 1, 130000.00, 'qris', 130000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-04-04 23:47:13.808661', 0.00);
INSERT INTO public.transactions VALUES (68, 'TRX-20260405-0001', 2, 1, 65000.00, 'tunai', 100000.00, 35000.00, NULL, 'paid', '', '2026-04-05 10:10:39.662166', 5000.00);
INSERT INTO public.transactions VALUES (86, 'TRX-20260405-0019', 1, 1, 290000.00, 'tunai', 500000.00, 210000.00, NULL, 'paid', '', '2026-04-05 18:31:33.58413', 10000.00);
INSERT INTO public.transactions VALUES (69, 'TRX-20260405-0002', 4, 1, 130000.00, 'qris', 130000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-04-05 10:22:09.460131', 0.00);
INSERT INTO public.transactions VALUES (70, 'TRX-20260405-0003', 4, 1, 225000.00, 'qris', 225000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-04-05 11:36:31.870775', 0.00);
INSERT INTO public.transactions VALUES (87, 'TRX-20260405-0020', 1, 1, 4900000.00, 'qris', 4900000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-04-05 18:33:42.58121', 0.00);
INSERT INTO public.transactions VALUES (71, 'TRX-20260405-0004', 4, 1, 130000.00, 'qris', 130000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-04-05 11:37:05.399104', 0.00);
INSERT INTO public.transactions VALUES (72, 'TRX-20260405-0005', 4, 1, 130000.00, 'qris', 130000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-04-05 11:37:43.67798', 0.00);
INSERT INTO public.transactions VALUES (73, 'TRX-20260405-0006', 4, 1, 130000.00, 'qris', 130000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-04-05 11:40:48.910115', 0.00);
INSERT INTO public.transactions VALUES (88, 'TRX-20260419-0001', 1, 1, 135000.00, 'qris', 135000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-04-19 15:09:22.264886', 0.00);
INSERT INTO public.transactions VALUES (74, 'TRX-20260405-0007', 4, 1, 260000.00, 'qris', 260000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-04-05 11:42:25.262776', 0.00);
INSERT INTO public.transactions VALUES (75, 'TRX-20260405-0008', 4, 1, 130000.00, 'qris', 130000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-04-05 11:42:54.750441', 0.00);
INSERT INTO public.transactions VALUES (89, 'TRX-20260419-0002', 1, 1, 135000.00, 'qris', 135000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-04-19 18:52:37.777534', 0.00);
INSERT INTO public.transactions VALUES (76, 'TRX-20260405-0009', 2, 1, 130000.00, 'qris', 130000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-04-05 11:44:08.751161', 0.00);
INSERT INTO public.transactions VALUES (90, 'TRX-20260419-0003', 1, 1, 85000.00, 'tunai', 100000.00, 15000.00, NULL, 'paid', '', '2026-04-19 18:53:43.62273', 10000.00);
INSERT INTO public.transactions VALUES (77, 'TRX-20260405-0010', 4, 1, 150000.00, 'qris', 150000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-04-05 11:44:32.987279', 0.00);
INSERT INTO public.transactions VALUES (91, 'TRX-20260520-0001', 2, 2, 225000.00, 'tunai', 250000.00, 25000.00, NULL, 'paid', NULL, '2026-05-20 15:22:44.369245', 0.00);
INSERT INTO public.transactions VALUES (78, 'TRX-20260405-0011', 2, 1, 130000.00, 'qris', 130000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-04-05 11:46:23.260355', 0.00);
INSERT INTO public.transactions VALUES (79, 'TRX-20260405-0012', 2, 1, 130000.00, 'qris', 130000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-04-05 11:49:15.096587', 0.00);
INSERT INTO public.transactions VALUES (92, 'TRX-20260620-0001', 1, 1, 95000.00, 'qris', 95000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-06-20 01:38:35.974963', 0.00);
INSERT INTO public.transactions VALUES (93, 'TRX-20260621-0001', 1, 1, 260000.00, 'tunai', 300000.00, 40000.00, NULL, 'paid', '', '2026-06-21 17:22:21.909514', 10000.00);
INSERT INTO public.transactions VALUES (94, 'TRX-20260621-0002', 1, 1, 90000.00, 'qris', 90000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-06-21 17:23:08.22434', 0.00);
INSERT INTO public.transactions VALUES (95, 'TRX-20260621-0003', 1, 1, 220000.00, 'qris', 220000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-06-21 17:23:49.285632', 0.00);
INSERT INTO public.transactions VALUES (96, 'TRX-20260621-0004', 1, 1, 130000.00, 'tunai', 150000.00, 20000.00, NULL, 'paid', '', '2026-06-21 17:24:23.9098', 0.00);
INSERT INTO public.transactions VALUES (97, 'TRX-20260621-0005', 1, 1, 90000.00, 'qris', 90000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-06-21 20:16:01.780284', 0.00);
INSERT INTO public.transactions VALUES (98, 'TRX-20260621-0006', 1, 1, 95000.00, 'qris', 95000.00, 0.00, NULL, 'failed', 'Pembayaran dibatalkan / expired', '2026-06-21 20:43:27.261972', 0.00);
INSERT INTO public.transactions VALUES (99, 'TRX-20260621-0007', 1, 1, 95000.00, 'qris', 95000.00, 0.00, NULL, 'failed', 'Pembayaran dibatalkan / expired', '2026-06-21 20:43:39.036436', 0.00);
INSERT INTO public.transactions VALUES (100, 'TRX-20260621-0008', 1, 1, 290000.00, 'qris', 290000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-06-21 21:32:17.757059', 0.00);
INSERT INTO public.transactions VALUES (101, 'TRX-20260621-0009', 1, 1, 95000.00, 'qris', 95000.00, 0.00, NULL, 'paid', 'Lunas via QRIS', '2026-06-21 22:16:14.142376', 0.00);
INSERT INTO public.transactions VALUES (102, 'TRX-20260621-0010', 1, 1, 883000.00, 'qris', 883000.00, 0.00, NULL, 'failed', 'Pembayaran dibatalkan / expired', '2026-06-21 22:25:10.287451', 0.00);


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.users VALUES (4, 'NUR', 'admin2@majmainsight.com', '$2y$12$pja1b2lKpnv8iKFl6S9Ouu0lhOghFj5IzMKr.fYoipRD1wfM7qVbC', 'pemilik', true, '2026-03-15 19:01:31.859593', '2026-06-21 17:29:22.893189');
INSERT INTO public.users VALUES (6, 'rahmat', 'rahmat@gmail.com', '$2y$12$2e4devTteDKiZyQVWdEBlOwaZIMkbQvZGEKjkdla5Xp0l.K9HFmL6', 'kasir', true, '2026-06-21 22:17:56.082569', '2026-06-21 22:17:56.082569');
INSERT INTO public.users VALUES (1, 'Ahmad Miftah', 'admin@majmainsight.com', '$2y$12$69/AVx8iH2AOk7a1ciGyZOmQPq3WdlruqrxnnseiW/HnRzXj0Z5ze', 'pemilik', true, '2026-03-07 01:11:35.336734', '2026-03-15 19:25:31.757956');
INSERT INTO public.users VALUES (5, 'Budi', 'admin3@majmainsight.com', '$2y$12$gkZBF5qBEWBUgw/qX/6nzOi2bpSha426k.PbLfLkJZ5fm9hFFXy2e', 'pemilik', false, '2026-03-15 20:03:04.526749', '2026-04-05 10:25:45.021912');
INSERT INTO public.users VALUES (3, 'Kasir2', 'kasir2@majmainsight.com', '$2y$12$Bg48uFavT4i2M6fi7.uonOO285kYD/Ir2Cj0irmQ7swYWFLiDqfOy', 'kasir', false, '2026-03-15 18:47:48.681212', '2026-04-05 10:25:51.152875');
INSERT INTO public.users VALUES (2, 'Kasir Utama', 'kasir@majmainsight.com', '$2y$12$Bbm94yp4V8pFK0xw4O9xeutLMmu0oTw9FZzptBG1yhrXtnpbi3yxm', 'kasir', true, '2026-03-07 01:11:35.336734', '2026-04-19 18:56:06.8367');


--
-- Data for Name: warehouses; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.warehouses VALUES (1, 'Gudang Utama', 'Rumah - Lantai 1', 'Gudang penyimpanan stok utama', true, '2026-03-07 01:11:35.336734');
INSERT INTO public.warehouses VALUES (2, 'Toko Pasar A', 'Pasar Sentral Kios 12', 'Lokasi jualan pasar pagi', true, '2026-03-07 01:11:35.336734');
INSERT INTO public.warehouses VALUES (3, 'Toko Pasar B', 'Pasar Minggu Kios 5', 'Lokasi jualan pasar mingguan', true, '2026-03-07 01:11:35.336734');


--
-- Name: products_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.products_id_seq', 121, true);


--
-- Name: stock_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.stock_id_seq', 82, true);


--
-- Name: stock_movements_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.stock_movements_id_seq', 156, true);


--
-- Name: transaction_items_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.transaction_items_id_seq', 125, true);


--
-- Name: transactions_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.transactions_id_seq', 102, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.users_id_seq', 6, true);


--
-- Name: warehouses_id_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.warehouses_id_seq', 3, true);


--
-- Name: products products_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_pkey PRIMARY KEY (id);


--
-- Name: products products_sku_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.products
    ADD CONSTRAINT products_sku_key UNIQUE (sku);


--
-- Name: stock_movements stock_movements_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_pkey PRIMARY KEY (id);


--
-- Name: stock stock_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.stock
    ADD CONSTRAINT stock_pkey PRIMARY KEY (id);


--
-- Name: stock stock_product_id_warehouse_id_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.stock
    ADD CONSTRAINT stock_product_id_warehouse_id_key UNIQUE (product_id, warehouse_id);


--
-- Name: transaction_items transaction_items_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transaction_items
    ADD CONSTRAINT transaction_items_pkey PRIMARY KEY (id);


--
-- Name: transactions transactions_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_pkey PRIMARY KEY (id);


--
-- Name: transactions transactions_transaction_code_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_transaction_code_key UNIQUE (transaction_code);


--
-- Name: users users_email_key; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_key UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: warehouses warehouses_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.warehouses
    ADD CONSTRAINT warehouses_pkey PRIMARY KEY (id);


--
-- Name: idx_movements_created; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_movements_created ON public.stock_movements USING btree (created_at DESC);


--
-- Name: idx_movements_product; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_movements_product ON public.stock_movements USING btree (product_id);


--
-- Name: idx_movements_type; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_movements_type ON public.stock_movements USING btree (type);


--
-- Name: idx_products_category; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_products_category ON public.products USING btree (category);


--
-- Name: idx_products_is_active; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_products_is_active ON public.products USING btree (is_active);


--
-- Name: idx_products_sku; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_products_sku ON public.products USING btree (sku);


--
-- Name: idx_stock_product; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_stock_product ON public.stock USING btree (product_id);


--
-- Name: idx_stock_warehouse; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_stock_warehouse ON public.stock USING btree (warehouse_id);


--
-- Name: idx_transactions_cashier; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_transactions_cashier ON public.transactions USING btree (cashier_id);


--
-- Name: idx_transactions_date; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_transactions_date ON public.transactions USING btree (transaction_date DESC);


--
-- Name: idx_transactions_status; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_transactions_status ON public.transactions USING btree (payment_status);


--
-- Name: idx_trx_items_product; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_trx_items_product ON public.transaction_items USING btree (product_id);


--
-- Name: idx_trx_items_transaction; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX idx_trx_items_transaction ON public.transaction_items USING btree (transaction_id);


--
-- Name: products trg_products_updated_at; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_products_updated_at BEFORE UPDATE ON public.products FOR EACH ROW EXECUTE FUNCTION public.fn_set_updated_at();


--
-- Name: users trg_users_updated_at; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER trg_users_updated_at BEFORE UPDATE ON public.users FOR EACH ROW EXECUTE FUNCTION public.fn_set_updated_at();


--
-- Name: stock_movements stock_movements_created_by_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_created_by_fkey FOREIGN KEY (created_by) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: stock_movements stock_movements_from_warehouse_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_from_warehouse_id_fkey FOREIGN KEY (from_warehouse_id) REFERENCES public.warehouses(id) ON DELETE SET NULL;


--
-- Name: stock_movements stock_movements_product_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_product_id_fkey FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE RESTRICT;


--
-- Name: stock_movements stock_movements_to_warehouse_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_to_warehouse_id_fkey FOREIGN KEY (to_warehouse_id) REFERENCES public.warehouses(id) ON DELETE SET NULL;


--
-- Name: stock_movements stock_movements_warehouse_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.stock_movements
    ADD CONSTRAINT stock_movements_warehouse_id_fkey FOREIGN KEY (warehouse_id) REFERENCES public.warehouses(id) ON DELETE SET NULL;


--
-- Name: stock stock_product_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.stock
    ADD CONSTRAINT stock_product_id_fkey FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE CASCADE;


--
-- Name: stock stock_warehouse_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.stock
    ADD CONSTRAINT stock_warehouse_id_fkey FOREIGN KEY (warehouse_id) REFERENCES public.warehouses(id) ON DELETE CASCADE;


--
-- Name: transaction_items transaction_items_product_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transaction_items
    ADD CONSTRAINT transaction_items_product_id_fkey FOREIGN KEY (product_id) REFERENCES public.products(id) ON DELETE RESTRICT;


--
-- Name: transaction_items transaction_items_transaction_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transaction_items
    ADD CONSTRAINT transaction_items_transaction_id_fkey FOREIGN KEY (transaction_id) REFERENCES public.transactions(id) ON DELETE CASCADE;


--
-- Name: transactions transactions_cashier_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_cashier_id_fkey FOREIGN KEY (cashier_id) REFERENCES public.users(id) ON DELETE SET NULL;


--
-- Name: transactions transactions_warehouse_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.transactions
    ADD CONSTRAINT transactions_warehouse_id_fkey FOREIGN KEY (warehouse_id) REFERENCES public.warehouses(id) ON DELETE SET NULL;


--
-- PostgreSQL database dump complete
--

\unrestrict czMmMDOJ8ObIltwrDa8UZuwycaqDUt8ehlMaMexiK8d7t0MjYWt0xdaIE9DGRIH

