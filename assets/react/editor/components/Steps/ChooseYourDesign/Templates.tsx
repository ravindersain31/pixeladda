import React, { useCallback, useEffect, useState } from "react";
import { useAppSelector } from "@react/editor/hook.ts";
import { Row, Col, message, Spin, Button } from "antd";
import { SearchOutlined } from "@ant-design/icons";
import axios from "axios";
import ProductCard from "./ProductCard";
import {
    TemplateContainer,
    CardHeader,
    ProductSearch,
    CardWrapper,
    Loading,
    NoTemplateBadge,
    NoTemplates,
    NoTemplateText,
    SearchWrapper,
    IconWrapper,
    InputWrapper,
    StyledInput
} from "./styled.tsx";
import { debounce } from "lodash";
import useArtworkUpload from "@react/editor/plugin/useArtworkUpload";
import { isPromoStore } from "@react/editor/helper/editor.ts";

const ChooseYourDesign = ({ name, slug, categoryId, onProductChange }: any) => {

    const [messageApi, contextHolder] = message.useMessage();

    const config = useAppSelector(state => state.config);

    const canvas = useAppSelector(state => state.canvas);

    const [isLoaded, setLoaded] = useState<boolean>(false);

    const [isLoading, setLoading] = useState<boolean>(false);

    const [currentProduct, setCurrentProduct] = useState<string | null>(null);

    const [templates, setTemplates] = useState<any>([]);
    const [page, setPage] = useState<number>(1);
    const [pageSize] = useState<number>(32);
    const [hasMore, setHasMore] = useState<boolean>(true);
    const [searchQuery, setSearchQuery] = useState<string>('');
    const [isSearchOpen, setSearchIsOpen] = useState<boolean>(false);

    useEffect(() => {
        (async () => {
            await loadProducts();
            await loadCustomTemplates();

            if (slug === 'die-cut') {
                await loadCustomTemplates('DC-CUSTOM');
            } else if (slug === 'big-head-cutouts') {
                await loadCustomTemplates('BHC-CUSTOM');
            } else if (slug === 'hand-fans') {
                await loadCustomTemplates('HF-CUSTOM');
            } else if (slug === 'custom-signs') {
                await loadCustomTemplates('BHC-CUSTOM');
                await loadCustomTemplates('DC-CUSTOM');
                await loadCustomTemplates('HF-CUSTOM');
            }

            setLoaded(true);
        })();
        setCurrentProduct(config.product.id);
    }, []);

    useEffect(() => {
        const pids = [
            ...config.product.variants.map((v: any) => v.productId),
            ...config.product.customVariant.map((v: any) => v.productId),
        ];
        if (!pids.includes(canvas.item.productId)) {
            setCurrentProduct(null);
        } else {
            setCurrentProduct(config.product.id);
        }
    }, [canvas.item]);

    useEffect(() => {
        setSearchQuery('');
    }, [categoryId])

    const loadCustomTemplates = async (sku: string = 'CUSTOM-SIGN') => {
        const order = ['CUSTOM', 'CUSTOM-SIGN', 'BHC-CUSTOM', 'DC-CUSTOM', 'HF-CUSTOM'];

        try {
            const { data, status } = await axios.get(`${config.links.product_sku}/${sku}`);
            if (status === 200) {
                setTemplates((prevTemplates: any) => {
                    const merged = [...data, ...prevTemplates];
                    if (slug === 'custom-signs') {
                        return merged.sort(
                            (a, b) => order.indexOf(a.sku) - order.indexOf(b.sku)
                        );
                    } else {
                        return merged;
                    }
                });
            }
        } catch (error) {
            console.error("Error loading custom templates:", error);
        }
    };

    const loadProducts = async (query: string | null = null, pageNo?: number) => {
        if (isLoading) return;
        setLoading(true);
        let url = `${config.links.list_products}?p=${pageNo ? pageNo : page}&limit=${pageSize}`;
        if (query) {
            url += `&q=${query}`;
        } else {
            url += `&c=${categoryId}`;
        }
        try {
            const { data, status } = await axios.get(url);
            if (status === 200) {
                const filteredData = data.filter(
                    (item: any) => item.sku !== "SAMPLE" && item.sku !== "WIRE-STAKE" && item.sku !== "DC-CUSTOM"
                );
                setTemplates((prevTemplates: any) => [...prevTemplates, ...filteredData]);
                setPage(prevPage => prevPage + 1);
                setHasMore(filteredData.length === pageSize);

                if (query && filteredData.length > 0 && pageNo === 1) {
                    await loadCustomTemplates();
                }
            }
        } catch (error) {
            console.error("Error loading products:", error);
        } finally {
            setLoading(false);
            setLoaded(true);
        }
    };


    const debouncedProductSearch = useCallback(debounce((value: any) => onProductSearchChange(value), 1000), []);

    const onProductSearchChange = async (value: string) => {
        setSearchQuery(value);
        setTemplates([]);
        setHasMore(true);
        setPage(1);
        setLoaded(false);
        await loadProducts(value, 1);
        if (!value) {
            await loadCustomTemplates();
        }
    };

    const onProductClick = async (template: any) => {
        setCurrentProduct(template.id);
        onProductChange(template.sku);
        useArtworkUpload();

    };

    const handleScroll = (event: React.UIEvent<HTMLDivElement>) => {
        const target = event.target as HTMLDivElement;
        if (target.scrollHeight - target.scrollTop <= target.clientHeight + 5 && hasMore) {
            loadProducts(searchQuery);
        }
    };

    const toggleSearch = () => setSearchIsOpen(!isSearchOpen);

    const LiveChat = (event: React.MouseEvent) => {
        //@ts-ignore
        Tawk_API.toggle();
    };

    return (
        <TemplateContainer>
            <SearchWrapper>
                <IconWrapper onClick={toggleSearch} $isOpen={isSearchOpen}>
                    <SearchOutlined />
                </IconWrapper>

                <InputWrapper className="template-input-wrapper" $isSearchOpen={isSearchOpen}>
                    <StyledInput
                        allowClear
                        enterButton
                        size="large"
                        value={searchQuery}
                        loading={isLoading}
                        disabled={isLoading}
                        placeholder={`Search templates`}
                        onChange={(event: any) => {
                            const value = event.currentTarget.value;
                            setSearchQuery(value);
                            debouncedProductSearch(value);
                        }}
                    />
                </InputWrapper>
            </SearchWrapper>
            <CardWrapper onScroll={handleScroll}>
                {isLoading && <Loading>Loading...</Loading>}
                <Row>
                    {isLoaded && templates.length <= 0 && (
                        <Col sm={24}>
                            <NoTemplates>
                                <NoTemplateBadge>
                                    Create Your Own Custom Outdoor Yard Sign! Select Any Template to Fully Customize!
                                </NoTemplateBadge>
                                <NoTemplateText>
                                    <h4>No Results</h4>
                                    <p>
                                        We did not find any results matching with your search query.
                                        Please try again or call us anytime at <a href="tel:+1-877-958-1499">+1-877-958-1499</a>, email <a href="mailto:sales@yardsignplus.com">sales@yardsignplus.com</a> or message us on our <Button type='link' onClick={LiveChat}>Live Chat</Button>
                                    </p>
                                </NoTemplateText>
                            </NoTemplates>
                        </Col>
                    )}
                    {templates.map((template: any) => {
                        return (
                            <Col xs={12} sm={6} key={`template_${template.id}`}>
                                <ProductCard
                                    onClick={() => {
                                        if (currentProduct !== template.id) {
                                            onProductClick(template);
                                        }
                                    }}
                                    key={template.id}
                                    title={template.sku}
                                    imageUrl={
                                        isPromoStore() && template?.promoImageUrl && !template.promoImageUrl.endsWith("/product/img/")
                                            ? template.promoImageUrl
                                            : template?.imageUrl
                                    }
                                    isActive={currentProduct === template.id}
                                />
                            </Col>
                        );
                    })}
                </Row>
                {isLoading && <Spin size="default" style={{ width: "100%", padding: "20px 0" }} />}
            </CardWrapper>
            {contextHolder}
        </TemplateContainer>
    );
};

export default ChooseYourDesign;
