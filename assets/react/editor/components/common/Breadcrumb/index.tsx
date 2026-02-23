import { useAppSelector } from "@react/editor/hook";
import { Button, Col, Row } from "antd";
import React from "react";
import { ShareDesignWrapper } from "./styled";
import ShareDesign from "../../ShareDesign";
import useShowCanvas from "@react/editor/hooks/useShowCanvas";
import { getQueryParam } from "@react/editor/helper/editor";

const Breadcrumb = () => {
	const product = useAppSelector(state => state.config.product);
	const showCanvas = useShowCanvas();

	const subCategory = getQueryParam("subCategory");
	const subCategorySlug = subCategory ? `&sc=${encodeURIComponent(subCategory)}` : '';

	const breadcrumbs =
		product.category.slug === "custom-signs"
			? [
				{
					name: product.category.name,
					link: "https://www.yardsignplus.com/shop?c=contractor,political,real-estate,business-ads,for-sale,sign-riders,foreclosure,restaurant,birthday,graduation,church,community,health-safety,protest",
				},
				{
					name: "Shop",
					link: "https://www.yardsignplus.com/shop?c=contractor,political,real-estate,business-ads,for-sale,sign-riders,foreclosure,restaurant,birthday,graduation,church,community,health-safety,protest",
				},
				{ name: product.sku, active: true },
			]
			: [
				{
					name: product.category.name,
					link: `/category/${product.category.slug}`,
				},
				{
					name: "Shop",
					link: `/shop?c=${product.category.slug}${subCategorySlug}`,
				},
				{ name: product.sku, active: true },
			];
	return (
		<>

			<nav
				className="breadcrumb-area"
				style={{
					"--bs-breadcrumb-divider": `url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8'%3E%3Cpath d='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z' fill='%236c757d'/%3E%3C/svg%3E")`,
				} as React.CSSProperties}
				aria-label="breadcrumb"
			>
				<ol className="breadcrumb">
					<li className="breadcrumb-item">
						<a href="/">Home</a>
					</li>
					{breadcrumbs.map((item, index) => (
						<li
							key={index}
							className={`breadcrumb-item ${item.active ? "active" : ""}`}
							aria-current={item.active ? "page" : undefined}
						>
							{item.link ? (
								<a href={item.link}>{item.name}</a>
							) : (
								<span>{item.name}</span>
							)}
						</li>
					))}
				</ol>
			</nav>
			<Row style={{ 'padding': '0 25px' }}>
				<Col span={24}>
					<section className="editor-sub-headings d-sm-block d-md-block px-0">
						<div className="editor-sub-headings-titles">
							<span>
								<a target="_blank" href="/contact-us">Contact Us</a>{' '}
								for Bulk Discounts |{' '}
								<Button type="link" className="ysp-delivery-calendar-btn mt-0 fs-15" style={{ 'outline': 'none' }} data-bs-toggle="modal" data-bs-target="#ysp-delivery-calendar">Overnight Delivery</Button>{' '}
								Available
							</span>
						</div>
					</section>
					{showCanvas && (
						<ShareDesignWrapper>
							<ShareDesign />
						</ShareDesignWrapper>
					)}
				</Col>
			</Row>
		</>
	);
};

export default Breadcrumb;
