import React, { useEffect, useState, useRef, useCallback } from "react";
import { Button, Spin, message, Popover, Typography } from "antd";
import type { FC } from "react";
const { Text } = Typography;


import { PricingTable } from "./styled";
import { isMobile } from "react-device-detect";

interface Pricing {
    qty: number;
    usd: number;
}

interface Product {
    id: number;
    sku: string;
    name: string;
    imageUrl: string;
    lowestPrice: number;
    primaryCategory: {
        slug: string;
    };
    productType: {
        slug: string;
    };
    productTypePricing?: Record<string, Pricing[]>;
}

interface VirtualProduct {
    id: number;
    sku: string;
    name: string;
    slug: string;
    categorySlug: string;
    categoryName: string;
    productTypeSlug: string;
    productTypeName: string;
    lowestPrice: string;
    imageName?: string | null;
    displayImageName?: string | null;
    productTypePricing?: Record<string, Pricing[]>;
    productPricing?: unknown[];
    title?: string;
    description?: string;
    keywords?: string;
}

const ribbonColors = ["blue", "green", "blue"];
const ribbons: Record<string, string[]> = {
    "24x18": ["Best Seller", "Standard"],
    "18x12": ["Best Seller"],
};

const Star = () => {
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            width="24"
            height="24"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth={2}
            strokeLinecap="round"
            strokeLinejoin="round"
            className="feather feather-star star"
        >
            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2" />
        </svg>
    )
}

const ProductList: FC = () => {
    const [products, setProducts] = useState<Product[]>([]);
    const [virtualProducts, setVirtualProducts] = useState<VirtualProduct[]>([]);
    const [page, setPage] = useState<number>(1);
    const [loading, setLoading] = useState<boolean>(false);
    const [endOfResults, setEndOfResults] = useState<boolean>(false);
    const loaderRef = useRef<HTMLDivElement | null>(null);

    const urlParams = new URLSearchParams(window.location.search);
    const categoryParam = urlParams.get("c");
    const subCategoryParam = urlParams.get("sc");
    const searchParams = urlParams.get("search");

    let categoryShopUrl = null;
    if (categoryParam && !subCategoryParam) {
        categoryShopUrl = "/shop";
    } else if (subCategoryParam) {
        categoryShopUrl = `/shop?c=${categoryParam}`;
    }

    let buttonLabel = "";
    if (categoryParam && !subCategoryParam) {
        buttonLabel = "Yard";
    } else if (subCategoryParam) {
        buttonLabel = `${categoryParam}`;
    }
    const buildParams = () => {
        let param = categoryParam ? `c=${categoryParam}` : `search=${searchParams || ""}`;
        if (subCategoryParam) {
            param += `&sc=${subCategoryParam}`;
        }
        return param;
    };

    const fetchProducts = useCallback(async () => {
        if (loading || endOfResults) return;

        setLoading(true);
        const params = buildParams();

        try {
            const response = await fetch(`/api/shop/product/lists?${params}&page=${page}`);
            const data: any = await response.json();
            const apiProducts: Product[] = data.products || [];
            const apiVirtuals: VirtualProduct[] = data.virtualProducts || [];
            const filteredData = apiProducts.filter(product => product.sku !== "DC-CUSTOM" && product.sku !== "BHC-CUSTOM" && product.sku !== "HF-CUSTOM" && product.sku !== "CUSTOM");

            if (apiVirtuals.length > 0) {
                setVirtualProducts((prev) => [...prev, ...apiVirtuals]);
            }
            if (filteredData.length > 0) {
                setProducts((prev) => [...prev, ...filteredData]);
                setPage((prev) => prev + 1);
            } else {
                setEndOfResults(true);
            }
        } catch (err: any) {
            message.error("Error fetching products: " + err.message);
            console.error("Fetch error:", err);
        } finally {
            setLoading(false);
        }
    }, [page, endOfResults, loading]);

    useEffect(() => {
        fetchProducts();
    }, []);

    useEffect(() => {
        const observer = new IntersectionObserver(
            (entries) => {
                if (entries[0].isIntersecting && !loading && !endOfResults) {
                    fetchProducts();
                }
            },
            { threshold: 1.0 }
        );

        if (loaderRef.current) observer.observe(loaderRef.current);

        return () => {
            if (loaderRef.current) observer.unobserve(loaderRef.current);
        };
    }, [fetchProducts, loaderRef.current]);

    useEffect(() => {
        const lazyImages = document.querySelectorAll("img.lazy");

        const observer = new IntersectionObserver((entries, imgObserver) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const img = entry.target as HTMLImageElement;
                    const realSrc = img.getAttribute("data-src");
                    if (realSrc) {
                        img.src = realSrc;
                        img.classList.remove("lazy");
                        imgObserver.unobserve(img);
                    }
                }
            });
        });

        lazyImages.forEach((img) => {
            observer.observe(img);
        });

        return () => {
            lazyImages.forEach((img) => observer.unobserve(img));
        };
    }, [products]);

    const getProductUrl = (product: Product) => {
        if (product.sku === "SAMPLE") return "/order-sample";
        if (product.sku === "WIRE-STAKE") return "/order-wire-stake";
        const subCategory = subCategoryParam ? `?subCategory=${subCategoryParam}` : '';
        return `/${product.primaryCategory.slug}/shop/${product.productType.slug}/${product.sku}${subCategory}`;
    };

    const getVirtualProductUrl = (vp: VirtualProduct) => {
        return `/shop/custom-${vp.slug}-yard-sign`;
    };

    const isVirtualProduct = (p: Product | VirtualProduct): p is VirtualProduct => {
        return (p as VirtualProduct).displayImageName !== undefined;
    };

    function addInchQuotes(variant: string): string {
        const parts = variant.split("x");
        if (parts.length === 2) {
            return `${parts[0]}" x ${parts[1]}"`;
        }
        return variant;
    }

    function reviewCount(): number {
        return Math.floor(Math.random() * (150 - 30 + 1)) + 30;
    }

    const pricingTable = (product: Product | VirtualProduct) => {
        const pricingEntries = Object.entries(product.productTypePricing || {});

        let firstValidEntry = pricingEntries.find(
            ([key, pricingArray]) => key === "pricing_12x12" && pricingArray && pricingArray.length > 0
        );

        if (isVirtualProduct(product)) {
            const targetKey = `pricing_${product.slug}`;
            firstValidEntry = pricingEntries.find(
                ([key, pricingArray]) =>
                    key === targetKey && pricingArray && pricingArray.length > 0
            );
        }

        if (!firstValidEntry) {
            firstValidEntry = pricingEntries.find(
                ([, pricingArray]) => pricingArray && pricingArray.length > 0
            );
        }

        if (!firstValidEntry) return null;

        const [firstKey, pricingArray] = firstValidEntry;

        const pricingData = pricingArray.map((price: any, index: number) => ({
            key: `${firstKey}-${index}`,
            quantity: index === 0 ? `${price.qty} (minimum)` : price.qty,
            price: `$${price.usd}`
        }));

        const size = firstKey.replace("pricing_", "").replace(/_/g, " ");

        const columns = [
            { title: "Quantity", dataIndex: "quantity", key: "quantity" },
            { title: "Price per Item", dataIndex: "price", key: "price" }
        ];

        const content = (
            <div style={{ fontFamily: "Montserrat, serif" }}>
                <PricingTable
                    columns={columns}
                    dataSource={pricingData}
                    pagination={false}
                    size="small"
                    rootClassName="shop-pricing-table"
                    bordered
                    style={{ fontSize: "12px" }}
                    className="compact-table"
                    rowClassName={() => "compact-row"}
                />
                <div style={{ marginTop: "10px", fontSize: "12px", lineHeight: "1.5" }}>
                    <strong>All-inclusive pricing includes:</strong><br />
                    <strong style={{ color: "#1C824C" }}>✓ FREE</strong> artwork review<br />
                    <strong style={{ color: "#1C824C" }}>✓ FREE</strong> artwork setup<br />
                    <strong style={{ color: "#1C824C" }}>✓ FREE</strong> delivery orders +$50<br />
                    <span style={{ fontSize: "10px", color: "#6c757d" }}>
                        *Table shown is {size}. Pricing varies by size. See <br />
                        our order page for details.
                    </span>
                </div>
            </div>
        );

        return (
            <Popover content={content} title={<span>Price Chart*</span>} trigger="hover">
                <Button style={{ fontFamily: "Montserrat, serif" }} type="link" title="Pricing" className="p-0 m-0" onClick={(e: any) => {
                    e.preventDefault();
                }}>
                    View Pricing
                </Button>
            </Popover>
        );
    };

    const OverNightDeliveryTag = () => {
        return (
            <span className="badge d-inline-flex align-items-center px-1 py-0 rounded-pill custom-badge-super-rush">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" className="bi bi-lightning-fill me-1" viewBox="0 0 16 16">
                    <path d="M11.3 1L4 9h4l-1 6 7-8H9l2.3-6z" />
                </svg>
                Overnight Delivery
            </span>
        );
    };

    return (
        <>
            {virtualProducts.map((vp, index) => {
                const image = vp.displayImageName ? vp.displayImageName : vp.imageName ? vp.imageName : "";
                const hasRibbon = !!ribbons[vp.slug];

                return (
                    <div
                        key={index}
                        className={`col-6 col-lg-4 col-xxl-3 product-item item-${vp.slug} pe-xl-1 ps-xl-1 mb-1 p-xl-1`}
                    >
                        {ribbons[vp.slug]?.map((ribbonText, ribbonIndex) => (
                            <div
                                key={ribbonIndex}
                                className={`custom-ribbon ribbon-${vp.slug} ${ribbonColors[ribbonIndex % ribbonColors.length]}`}
                                style={{ top: `${5 + ribbonIndex * 25}px`, marginRight: isMobile ? '5%' : '2%' }}
                            >
                                {ribbonText}
                            </div>
                        ))}
                        <a href={getVirtualProductUrl(vp)} className={`card home-page-card`}>
                            <div className="card-body">
                                <div className="postion-relative p-2">
                                    <img
                                        width="246"
                                        height="184"
                                        className="product-image lazy"
                                        src={"https://static.yardsignplus.com/product/fit-in/800x800/img/" + image}
                                        data-src={"https://static.yardsignplus.com/product/fit-in/800x800/img/" + image}
                                        alt={vp.name}
                                    />
                                </div>
                                <h3 className="card-text p-1 p-sm-2 pb-0 mb-0">Custom {addInchQuotes(vp.name)} Outdoor Yard Signs</h3>
                            </div>
                            <ul className="list-group list-group-flush border-0">
                                <li className="list-group-item border-0">
                                    <OverNightDeliveryTag />
                                </li>
                                {pricingTable(vp)}
                                <li className="list-group-item bg-transparent">
                                    <div className="product-meta-data card-text">
                                        <div className="product-price">${vp.lowestPrice}</div>
                                        <div className="product-min-qty">Min 1</div>
                                        <div className="product-rating-star">
                                            {Array(5)
                                                .fill(0)
                                                .map((_, idx) => (
                                                    <span key={idx} data-rating={idx + 1}>
                                                        <Star />
                                                    </span>
                                                ))
                                            }
                                        </div>
                                        <div className="product-rating-count">({reviewCount()})</div>
                                    </div>
                                </li>
                            </ul>
                            <div className="card-button">
                                <button className="btn btn-light btn-block btn-customize-now">
                                    Customize Now
                                </button>
                            </div>
                        </a>
                    </div >
                )
            })}
            {products.map((product) => (
                <div
                    key={product.id}
                    className="col-6 col-lg-4 col-xxl-3 product-item pe-xl-1 ps-xl-1 mb-1 p-xl-1"
                >
                    <a href={getProductUrl(product)} className="card">
                        <div className="card-body">
                            <div className="postion-relative p-2">
                                <img
                                    width="246"
                                    height="184"
                                    className="product-image lazy"
                                    src={product.imageUrl.replace(
                                        "static.yardsignplus.com",
                                        "static.yardsignplus.com/filters:blur(30)/fit-in/200x200"
                                    )}
                                    data-src={product.imageUrl}
                                    alt={product.name}
                                />
                            </div>
                            <h3 className="card-text p-1 p-sm-2 pb-0 mb-0">{product.name}</h3>
                        </div>
                        <ul className="list-group list-group-flush border-0">
                            <li className="list-group-item border-0">
                                <OverNightDeliveryTag />
                            </li>
                            {pricingTable(product)}
                            <li className="list-group-item bg-transparent">
                                <div className="product-meta-data card-text">
                                    <div className="product-price">${product.lowestPrice}</div>
                                    <div className="product-min-qty">Min 1</div>
                                    <div className="product-rating-star">
                                        {Array(5)
                                            .fill(0)
                                            .map((_, idx) => (
                                                <span key={idx} data-rating={idx + 1}>
                                                    <Star />
                                                </span>
                                            ))
                                        }
                                    </div>
                                    <div className="product-rating-count">({reviewCount()})</div>
                                </div>
                            </li>
                        </ul>
                        <div className="card-button">
                            <button className="btn btn-light btn-block btn-customize-now">
                                Customize Now
                            </button>
                        </div>
                    </a>
                </div>
            ))}
            <div ref={loaderRef} style={{ width: "100%", textAlign: "center", margin: "20px 0" }}>
                {loading && <Spin size="large" />}
                {endOfResults && (
                    categoryShopUrl ? (
                        <a href={categoryShopUrl} className="btn btn-ysp-outline">
                            View All {buttonLabel} Signs
                        </a>
                    ) : (
                        <p>No more products to show.</p>
                    )
                )}
            </div>
        </>
    );
};

export default ProductList;
