import React, { useState } from "react";
import { Tabs } from "antd";
import { AppstoreOutlined, PictureOutlined, ApartmentOutlined } from "@ant-design/icons";
import ProofTemplateForm from "./Variant";
import ProofGrommetTemplateForm from "./Grommet";
import ProofWireStakeTemplateForm from "./WireStake";

interface Props {
    initialTemplates: any[];
    initialGrommetTemplates: any[];
    initialWireStakeTemplates: any[];
    variantChoices: string[];
    frameVariants: any[];
    grommetColors: any[];
}

const ProofTemplateManagementPage: React.FC<Props> = ({
    initialTemplates,
    initialGrommetTemplates,
    initialWireStakeTemplates,
    variantChoices,
    frameVariants,
    grommetColors
}) => {
    const getInitialTab = () => {
        const params = new URLSearchParams(window.location.search);
        const tab = params.get('tab');
        const validTabs = ['variants', 'grommets', 'wirestakes'];
        return (tab && validTabs.includes(tab)) ? tab : "variants";
    };

    const [activeTab, setActiveTab] = useState(getInitialTab());

    const handleTabChange = (key: string) => {
        setActiveTab(key);
        const url = new URL(window.location.href);
        url.searchParams.set('tab', key);
        window.history.pushState({}, '', url.toString());
    };

    const items = [
        {
            key: "variants",
            label: (
                <span>
                    <AppstoreOutlined style={{ marginRight: "5px" }} />
                    Proof Templates
                </span>
            ),
            children: (
                <ProofTemplateForm
                    initialTemplates={initialTemplates}
                    variantChoices={variantChoices}
                    frameVariants={frameVariants}
                />
            ),
        },
        {
            key: "grommets",
            label: (
                <span>
                    <PictureOutlined style={{ marginRight: "5px" }} />
                    Grommets
                </span>
            ),
            children: (
                <ProofGrommetTemplateForm
                    initialGrommetTemplates={initialGrommetTemplates}
                    grommetColors={grommetColors}
                />
            ),
        },
        {
            key: "wirestakes",
            label: (
                <span>
                    <ApartmentOutlined style={{ marginRight: "5px" }} />
                    Wire Stakes
                </span>
            ),
            children: (
                <ProofWireStakeTemplateForm
                    initialWireStakeTemplates={initialWireStakeTemplates}
                    frameVariants={frameVariants}
                />
            ),
        },
    ];

    return (
        <div style={{ maxWidth: 1600, margin: '0 auto', padding: '20px' }}>
            <Tabs
                activeKey={activeTab}
                onChange={handleTabChange}
                items={items}
                size="large"
                type="card"
            />
        </div>
    );
};

export default ProofTemplateManagementPage;